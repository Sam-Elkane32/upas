<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen forms text columns for long KPI/KRA/strategic goal content (PostgreSQL / Supabase).
     * MySQL-only migrations left kpi_title at VARCHAR(255) on pgsql.
     */
    public function up(): void
    {
        if (!Schema::hasTable('forms')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        $columns = ['kpi_title', 'kra_title', 'strategic_goal', 'form_title', 'responsible_unit'];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('forms', $column)) {
                continue;
            }

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE forms ALTER COLUMN {$column} TYPE TEXT");
            } elseif ($driver === 'mysql') {
                DB::statement("ALTER TABLE forms MODIFY COLUMN {$column} LONGTEXT NULL");
            }
        }
    }

    public function down(): void
    {
        // Non-destructive for production data.
    }
};
