<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen templates/submissions text columns for long KPI/KRA content (PostgreSQL / Supabase).
     * MySQL-only migrations left kpi_title at VARCHAR(255) on pgsql.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        $tableColumns = [
            'templates' => ['kpi_title', 'kra_title', 'sg_code', 'template_code'],
            'submissions' => ['kpi_title', 'kra_title', 'form_title', 'sg_code'],
        ];

        foreach ($tableColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                if ($driver === 'pgsql') {
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE TEXT");
                } elseif ($driver === 'mysql') {
                    DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$column} LONGTEXT NULL");
                }
            }
        }
    }

    public function down(): void
    {
        // Non-destructive for production data.
    }
};
