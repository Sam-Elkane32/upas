<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add template_id so each submission is tied to a specific template instance
     * (fixes new templates showing data from other templates with the same template_code).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('submissions', 'template_id')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->after('id');
                // Skip foreign key on SQLite to avoid autoindex name conflicts
                if (config('database.default') !== 'sqlite') {
                    $table->foreign('template_id')->references('id')->on('templates')->onDelete('set null');
                }
            });
        }

        // Backfill: set template_id from first matching template by template_code
        $submissions = DB::table('submissions')->whereNull('template_id')->get();
        foreach ($submissions as $sub) {
            $template = DB::table('templates')->where('template_code', $sub->template_code)->orderBy('id')->first();
            if ($template) {
                DB::table('submissions')->where('id', $sub->id)->update(['template_id' => $template->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (config('database.default') !== 'sqlite') {
                $table->dropForeign(['template_id']);
            }
            $table->dropColumn('template_id');
        });
    }
};
