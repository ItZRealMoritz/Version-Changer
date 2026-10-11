<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('version_changes')) {
            return;
        }

        Schema::table('version_changes', function (Blueprint $table) {
            if (!Schema::hasColumn('version_changes', 'stage')) {
                $table->string('stage')->nullable()->after('status');
            }
            if (!Schema::hasColumn('version_changes', 'context')) {
                $table->longText('context')->nullable()->after('stage');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('version_changes')) {
            return;
        }

        Schema::table('version_changes', function (Blueprint $table) {
            if (Schema::hasColumn('version_changes', 'context')) {
                $table->dropColumn('context');
            }
            if (Schema::hasColumn('version_changes', 'stage')) {
                $table->dropColumn('stage');
            }
        });
    }
};
