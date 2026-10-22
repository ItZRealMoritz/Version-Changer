<?php

namespace Pelican\Versions\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Pelican\Versions\Models\VersionChange;
use Pelican\Versions\Services\VersionChangeService;
use Throwable;

/**
 * Optional background driver.
 *
 * The version change is normally driven step-by-step by the Livewire poller on
 * the Version Changer page, which needs no queue worker. This job is kept for
 * advanced setups that prefer to run the whole change on a dedicated queue
 * worker: it simply drives the same re-entrant state machine to completion.
 */
class ChangeVersionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function __construct(
        public readonly int $changeRecordId
    ) {
        $queue = config('minecraft-version-changer.queue', null);
        if (!empty($queue)) {
            $this->onQueue($queue);
        }
    }

    public function handle(VersionChangeService $service): void
    {
        $record = VersionChange::find($this->changeRecordId);

        if (!$record) {
            Log::warning("[Versions] ChangeVersionJob: Record #{$this->changeRecordId} not found, skipping.");
            return;
        }

        if (!$record->isActive()) {
            Log::info("[Versions] ChangeVersionJob: Record #{$this->changeRecordId} is {$record->status}; skipping.");
            return;
        }

        $record->appendLog('Job started on queue worker.');

        $guardDeadline = time() + $this->timeout;

        while ($record->isActive()) {
            $service->advance($record);
            $record->refresh();

            if (!$record->isActive()) {
                break;
            }

            if (time() >= $guardDeadline) {
                $record->markFailed('Background job timed out before the change finished.');
                $record->appendLog('ERROR: Background job timed out.');
                break;
            }

            sleep(2);
        }
    }

    public function failed(Throwable $e): void
    {
        $record = VersionChange::find($this->changeRecordId);

        if ($record) {
            $record->markFailed($e->getMessage());
            $record->appendLog('JOB FAILED: ' . $e->getMessage());
        }

        Log::error("[Versions] ChangeVersionJob failed for record #{$this->changeRecordId}", [
            'error' => $e->getMessage(),
        ]);
    }
}
