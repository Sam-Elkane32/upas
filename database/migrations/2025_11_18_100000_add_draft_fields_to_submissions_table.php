<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('submissions', 'is_draft')) {
                $table->boolean('is_draft')->default(false);
            }
            if (!Schema::hasColumn('submissions', 'draft_version')) {
                $table->unsignedInteger('draft_version')->default(1);
            }
            if (!Schema::hasColumn('submissions', 'last_draft_at')) {
                $table->timestamp('last_draft_at')->nullable();
            }
        });

        // Add index separately to avoid issues with SQLite
        try {
            Schema::table('submissions', function (Blueprint $table) {
                // Check if index doesn't already exist
                $indexName = 'submissions_submitted_by_is_draft_status_index';
                $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name=?", [$indexName]);
                if (empty($indexes)) {
                    $table->index(['submitted_by', 'is_draft', 'status'], 'submissions_submitted_by_is_draft_status_index');
                }
            });
        } catch (\Exception $e) {
            // Index might already exist or there might be an issue, continue anyway
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['submitted_by', 'is_draft', 'status']);
            $table->dropColumn(['is_draft', 'draft_version', 'last_draft_at']);
        });
    }
};

