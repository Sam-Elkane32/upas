<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow forms without campus on create; campus is set via template assignment.
     */
    public function up(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'campus_code')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE forms DROP CONSTRAINT IF EXISTS forms_campus_code_foreign');
            DB::statement('ALTER TABLE forms ALTER COLUMN campus_code DROP NOT NULL');
        } elseif ($driver === 'mysql') {
            try {
                Schema::table('forms', function ($table) {
                    $table->dropForeign(['campus_code']);
                });
            } catch (\Throwable) {
                // FK may already be dropped.
            }
            DB::statement('ALTER TABLE forms MODIFY COLUMN campus_code VARCHAR(50) NULL');
        }
    }

    public function down(): void
    {
        // Non-destructive for production data.
    }
};
