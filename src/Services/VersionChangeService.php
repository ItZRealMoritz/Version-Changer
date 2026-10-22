<?php

namespace Pelican\Versions\Services;

use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use App\Repositories\Daemon\DaemonServerRepository;
use Pelican\Versions\Models\VersionChange;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class VersionChangeService
{
    private DaemonServerRepository $serverRepo;

    public function __construct(
        private DaemonFileRepository $fileRepo,
        ?DaemonServerRepository $serverRepo = null
    ) {
        $this->serverRepo = $serverRepo ?? app(DaemonServerRepository::class);
    }

    public function advance(VersionChange $record): void
    {
        $server = $record->server;

        if (!$server) {
            $record->markFailed("Server not found for VersionChange #{$record->id}");
            return;
        }

        $this->fileRepo->setServer($server);
        $this->serverRepo->setServer($server);

        try {
            switch ($record->currentStage()) {
                case VersionChange::STAGE_INIT:
                    $this->stageInit($record, $server);
                    break;
                case VersionChange::STAGE_STOPPING:
                    $this->stageStopping($record);
                    break;
                case VersionChange::STAGE_BACKUP:
                    $this->stageBackupAndClean($record);
                    break;
                case VersionChange::STAGE_DOWNLOAD:
                    $this->stageDownload($record);
                    break;
                case VersionChange::STAGE_VERIFY:
                    $this->stageVerifyAndExtract($record);
                    break;
                case VersionChange::STAGE_FINALIZE:
                    $this->stageFinalize($record, $server);
                    break;
                case VersionChange::STAGE_RESTART:
                    $this->stageRestart($record);
                    break;
                default:
                    $record->markDone();
            }
        } catch (Throwable $e) {
            $record->markFailed($e->getMessage());
            $record->appendLog('ERROR: ' . $e->getMessage());

            Log::error("[Versions] Version change failed for #{$record->id}: " . $e->getMessage(), [
                'server_id' => $server->id,
                'software'  => $record->software,
                'version'   => $record->minecraft_version,
                'stage'     => $record->currentStage(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }
    }

    private function stageInit(VersionChange $record, Server $server): void
    {
        $record->markChanging();

        $targetJar = $this->getTargetServerJarFilename($server);
        $record->setCtx(['target_jar' => $targetJar]);

        $record->appendLog("Starting change to {$record->software} {$record->minecraft_version} ({$record->build_name}).");
        $record->appendLog("Target server executable: {$targetJar}");

        $record->setStage(VersionChange::STAGE_STOPPING);
    }

    private function stageStopping(VersionChange $record): void
    {
        if (!config('minecraft-version-changer.auto_stop_server', true)) {
            $record->appendLog('Step 1/6: Automatic power handling is disabled.');
            $record->appendLog('  WARNING: Make sure the server is stopped manually before continuing.');
            $record->setCtx(['was_running' => false]);
            $record->setStage(VersionChange::STAGE_BACKUP);
            return;
        }

        try {
            $details = $this->serverRepo->getDetails();
        } catch (Throwable $e) {
            Log::debug('[Versions] stopping getDetails error: ' . $e->getMessage());
            return;
        }

        $state = strtolower($details['state'] ?? 'offline');
        $offlineStates = ['offline', 'exited', 'stopped'];
        $runningStates = ['running', 'starting', 'restarting'];

        if ($record->ctx('stop_started_at') === null) {
            $wasRunning = in_array($state, $runningStates, true);
            $record->setCtx([
                'was_running'     => $wasRunning,
                'stop_started_at' => now()->timestamp,
                'stop_signal_sent' => false,
            ]);

            if (in_array($state, $offlineStates, true)) {
                $record->appendLog('Step 1/6: Server is already offline. Ready for file replacement.');
                $record->setStage(VersionChange::STAGE_BACKUP);
                return;
            }

            if ($wasRunning) {
                $record->appendLog("Step 1/6: Server is online ({$state}).");
                $record->appendLog('  Sending a graceful stop signal (no kill) and waiting for a clean shutdown...');
            } else {
                $record->appendLog("Step 1/6: Server is shutting down ({$state}). Waiting for it to stop...");
            }
        }

        if (in_array($state, $offlineStates, true)) {
            $record->appendLog('  Server stopped cleanly. Proceeding with file replacement.');
            $record->setStage(VersionChange::STAGE_BACKUP);
            return;
        }

        if (!$record->ctx('stop_signal_sent') && in_array($state, $runningStates, true)) {
            $this->serverRepo->power('stop');
            $record->setCtx(['stop_signal_sent' => true]);
        }

        $elapsed = now()->timestamp - (int) $record->ctx('stop_started_at', now()->timestamp);
        $maxWait = (int) config('minecraft-version-changer.stop_timeout', 180);

        if ($elapsed >= $maxWait) {
            throw new RuntimeException(
                "Server did not stop within {$maxWait}s. Installation aborted so no files are touched while it runs. " .
                "No kill signal was sent — please stop the server manually and try again."
            );
        }

        $lastTick = (int) $record->ctx('stop_last_tick', 0);
        if ($elapsed - $lastTick >= 15) {
            $record->appendLog("  Still waiting for shutdown... ({$elapsed}s / {$maxWait}s, state: {$state})");
            $record->setCtx(['stop_last_tick' => $elapsed]);
        }
    }

    public function getTargetServerJarFilename(Server $server): string
    {
        try {
            $serverVar = $server->serverVariables()
                ->whereHas('variable', fn ($q) => $q->where('env_variable', 'SERVER_JARFILE'))
                ->first();

            $val = trim((string) ($serverVar?->variable_value ?? ''));
            if (empty($val) && $serverVar?->variable) {
                $val = trim((string) ($serverVar->variable->default_value ?? ''));
            }

            if (!empty($val)) {
                $clean = basename(str_replace('\\', '/', $val));
                if (!empty($clean)) {
                    return $clean;
                }
            }
        } catch (Throwable $e) {
            Log::debug('[Versions] getTargetServerJarFilename error: ' . $e->getMessage());
        }

        return 'server.jar';
    }

    private function stageBackupAndClean(VersionChange $record): void
    {
        $targetJar = (string) $record->ctx('target_jar', 'server.jar');

        $record->appendLog('Step 2/6: Backing up existing files and cleaning legacy libraries...');

        try {
            $entries = (array) $this->fileRepo->getDirectory('/');

            $jarsToCheck = array_unique(array_filter([$targetJar, 'server.jar']));

            foreach ($jarsToCheck as $jarName) {
                $hasJar = collect($entries)->contains(
                    fn ($e) => ($e['name'] ?? '') === $jarName && !($e['directory'] ?? false)
                );

                if ($hasJar) {
                    $bakName = "{$jarName}.bak";
                    if (config('minecraft-version-changer.keep_backup', true)) {
                        $hasOldBak = collect($entries)->contains(
                            fn ($e) => ($e['name'] ?? '') === $bakName && !($e['directory'] ?? false)
                        );

                        if ($hasOldBak) {
                            $this->fileRepo->deleteFiles('/', [$bakName]);
                            $record->appendLog("  Removed previous {$bakName} backup.");
                        }

                        $this->fileRepo->renameFiles('/', [
                            ['from' => $jarName, 'to' => $bakName]
                        ]);

                        $record->appendLog("  Renamed existing {$jarName} -> {$bakName}.");
                    } else {
                        $this->fileRepo->deleteFiles('/', [$jarName]);
                        $record->appendLog("  Deleted previous {$jarName} (backup disabled).");
                    }
                }
            }

            if (config('minecraft-version-changer.clean_libraries', true)) {
                $hasLibraries = collect($entries)->contains(
                    fn ($e) => ($e['name'] ?? '') === 'libraries' && ($e['directory'] ?? false)
                );

                if ($hasLibraries) {
                    $this->fileRepo->deleteFiles('/', ['libraries']);
                    $record->appendLog('  Removed old /libraries/ directory to prevent dependency collisions.');
                }
            }

            if ($record->clean_install) {
                $record->appendLog('  [CLEAN INSTALL] Wiping server root files...');

                $currentEntries = (array) $this->fileRepo->getDirectory('/');

                $preserved = array_unique([$targetJar . '.bak', 'server.jar.bak', 'eula.txt']);
                $toDelete  = [];

                foreach ($currentEntries as $entry) {
                    $name = $entry['name'] ?? '';
                    if (empty($name) || in_array($name, $preserved, true)) {
                        continue;
                    }
                    $toDelete[] = $name;
                }

                if (!empty($toDelete)) {
                    foreach (array_chunk($toDelete, 100) as $chunk) {
                        $this->fileRepo->deleteFiles('/', $chunk);
                    }
                    $record->appendLog('  [CLEAN INSTALL] Deleted ' . count($toDelete) . ' items from server root.');
                } else {
                    $record->appendLog('  [CLEAN INSTALL] Server root is already empty.');
                }
            }
        } catch (Throwable $e) {
            throw new RuntimeException('Backup/Clean step failed: ' . $e->getMessage(), 0, $e);
        }

        $record->setStage(VersionChange::STAGE_DOWNLOAD);
    }

    private function stageDownload(VersionChange $record): void
    {
        $targetJar = (string) $record->ctx('target_jar', 'server.jar');

        if (!$record->ctx('pull_sent')) {
            $urlPath = parse_url($record->jar_url, PHP_URL_PATH) ?? '';
            $isZip = str_ends_with(strtolower($urlPath), '.zip')
                || str_contains(strtolower($record->jar_url), '.zip');

            $filename = $isZip ? 'server.zip' : $targetJar;

            $record->appendLog('Step 3/6: Telling the Wings daemon to pull the new files...');
            $record->appendLog("  Target: {$record->software} {$record->minecraft_version} ({$record->build_name}) -> {$filename}");

            try {
                $this->fileRepo->pull($record->jar_url, '/', [
                    'filename'   => $filename,
                    'foreground' => false,
                ]);
            } catch (Throwable $e) {
                throw new RuntimeException("Failed to send download command to Wings: {$e->getMessage()}", 0, $e);
            }

            $record->setCtx([
                'is_zip'        => $isZip,
                'dl_filename'   => $filename,
                'pull_sent'     => true,
                'dl_started_at' => now()->timestamp,
                'last_size'     => -1,
                'stable_count'  => 0,
            ]);

            $record->appendLog('  Waiting for the download to begin on the node...');
            return;
        }

        $filename = (string) $record->ctx('dl_filename', $targetJar);
        $size = $this->getRemoteFileSize($filename);
        $elapsed = now()->timestamp - (int) $record->ctx('dl_started_at', now()->timestamp);
        $timeout = (int) config('minecraft-version-changer.download_timeout', 600);

        if ($size === null || $size <= 0) {
            if ($elapsed >= $timeout) {
                throw new RuntimeException("Download timed out: {$filename} was not created by Wings.");
            }
            return;
        }

        if ($record->jar_size && $size === (int) $record->jar_size) {
            $record->appendLog('  Download complete: ' . $this->humanBytes($size) . ' (100%)');
            $record->setStage(VersionChange::STAGE_VERIFY);
            return;
        }

        $lastSize = (int) $record->ctx('last_size', -1);

        if ($size === $lastSize) {
            $stable = (int) $record->ctx('stable_count', 0) + 1;
            $record->setCtx(['stable_count' => $stable]);
            if ($stable >= 2) {
                $record->appendLog('  Download finished: ' . $this->humanBytes($size));
                $record->setStage(VersionChange::STAGE_VERIFY);
                return;
            }
        } else {
            $pct = $record->jar_size ? ' (' . round(($size / $record->jar_size) * 100, 1) . '%)' : '';
            $record->appendLog('  Downloaded ' . $this->humanBytes($size) . $pct);
            $record->setCtx(['stable_count' => 0]);
        }

        $record->setCtx(['last_size' => $size]);

        if ($elapsed >= $timeout) {
            throw new RuntimeException("Download timed out after {$timeout}s.");
        }
    }

    private function stageVerifyAndExtract(VersionChange $record): void
    {
        $targetJar = (string) $record->ctx('target_jar', 'server.jar');
        $isZip = (bool) $record->ctx('is_zip', false);
        $filename = $isZip ? 'server.zip' : $targetJar;

        $record->appendLog("Step 4/6: Verifying {$filename} integrity...");

        $actual = $this->getRemoteFileSize($filename);

        if ($actual === null || $actual <= 0) {
            throw new RuntimeException("Verification failed: {$filename} not found after download.");
        }

        if ($record->jar_size) {
            $tolerance = (int) max(1024, $record->jar_size * 0.01);
            if (abs($actual - $record->jar_size) > $tolerance) {
                throw new RuntimeException(
                    "Size mismatch: expected {$record->jar_size} bytes, got {$actual} bytes."
                );
            }
            $record->appendLog('  File size verified: ' . $this->humanBytes($actual) . ' (matches expected)');
        } else {
            $record->appendLog('  File size verified: ' . $this->humanBytes($actual));
        }

        if ($isZip) {
            $record->appendLog('  Extracting server.zip archive to root directory...');
            try {
                $this->fileRepo->decompressFile('/', 'server.zip');
                $record->appendLog('  Decompression finished successfully.');

                try {
                    $this->fileRepo->deleteFiles('/', ['server.zip']);
                    $record->appendLog('  Cleaned up temporary server.zip.');
                } catch (Throwable $e) {
                    Log::debug('[Versions] zip cleanup failed: ' . $e->getMessage());
                }

                if ($targetJar !== 'server.jar') {
                    $rootEntries = (array) $this->fileRepo->getDirectory('/');
                    $hasServerJar = collect($rootEntries)->contains(
                        fn ($e) => ($e['name'] ?? '') === 'server.jar' && !($e['directory'] ?? false)
                    );
                    if ($hasServerJar) {
                        $this->fileRepo->renameFiles('/', [
                            ['from' => 'server.jar', 'to' => $targetJar]
                        ]);
                        $record->appendLog("  Aligned extracted server.jar to configured {$targetJar}.");
                    }
                }
            } catch (Throwable $e) {
                throw new RuntimeException("Failed to decompress {$filename}: " . $e->getMessage(), 0, $e);
            }
        }

        $record->setStage(VersionChange::STAGE_FINALIZE);
    }

    private function stageFinalize(VersionChange $record, Server $server): void
    {
        $record->appendLog('Step 5/6: Verifying Minecraft EULA status...');

        try {
            $content = $this->fileRepo->getContent('eula.txt', 2048);
            if (str_contains($content, 'eula=true')) {
                $record->appendLog('  Existing Minecraft EULA agreement detected and preserved.');
            } else {
                $record->appendLog('  Notice: Minecraft EULA is handled via Pelican native prompt on startup.');
            }
        } catch (Throwable $e) {
            Log::debug('[Versions] EULA check failed: ' . $e->getMessage());
            $record->appendLog('  Notice: Minecraft EULA is handled via Pelican native prompt on startup.');
        }

        $this->syncEggVariable($record, $server);

        $record->setStage(VersionChange::STAGE_RESTART);
    }

    private function syncEggVariable(VersionChange $record, Server $server): void
    {
        try {
            $serverVar = $server->serverVariables()
                ->whereHas('variable', fn ($q) => $q->where('env_variable', 'SERVER_JARFILE'))
                ->first();

            if ($serverVar) {
                $targetJar = (string) $record->ctx('target_jar', $this->getTargetServerJarFilename($server));
                $currentVal = trim((string) $serverVar->variable_value);

                if (empty($currentVal) || $currentVal !== $targetJar) {
                    $serverVar->update(['variable_value' => $targetJar]);
                    $record->appendLog("  Synchronized SERVER_JARFILE egg variable to \"{$targetJar}\".");
                    try {
                        $this->serverRepo->sync();
                        $record->appendLog('  Synced environment configuration with the Wings node.');
                    } catch (Throwable $e) {
                        Log::debug('[Versions] sync environment failed: ' . $e->getMessage());
                    }
                } else {
                    $record->appendLog("  SERVER_JARFILE egg variable verified: \"{$targetJar}\".");
                }
            }
        } catch (Throwable $e) {
            Log::debug('[Versions] syncEggVariable failed: ' . $e->getMessage());
        }
    }

    private function stageRestart(VersionChange $record): void
    {
        if ($record->ctx('was_running') && config('minecraft-version-changer.auto_restart_server', true)) {
            $record->appendLog('Step 6/6: Restarting the server with the new version...');
            try {
                $this->serverRepo->power('start');
                $record->appendLog('  Sent start signal to Wings. Server is booting up!');
            } catch (Throwable $e) {
                $record->appendLog('  Notice: Could not send start signal: ' . $e->getMessage() . '. Please start it manually.');
            }
            $record->markDone();
            $record->appendLog('Version change completed successfully! Server has been automatically restarted.');
        } else {
            $record->appendLog('Step 6/6: Server was offline and remains offline. Ready to start.');
            $record->markDone();
            $record->appendLog('Version change completed successfully! You can now start your server.');
        }
    }

    private function getRemoteFileSize(string $filename): ?int
    {
        try {
            $entries = (array) $this->fileRepo->getDirectory('/');
            foreach ($entries as $entry) {
                if (($entry['name'] ?? '') === $filename && !($entry['directory'] ?? false)) {
                    return (int) ($entry['size'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            Log::debug('[Versions] getRemoteFileSize failed: ' . $e->getMessage());
        }

        return null;
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
