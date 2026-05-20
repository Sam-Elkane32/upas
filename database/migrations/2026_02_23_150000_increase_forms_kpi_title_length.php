<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Increase kpi_title column length to support long concatenated KPI text.
     */
    public function up(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'kpi_title')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE forms ALTER COLUMN kpi_title TYPE TEXT');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE forms MODIFY COLUMN kpi_title LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'kpi_title')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE forms ALTER COLUMN kpi_title TYPE VARCHAR(255)');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE forms MODIFY COLUMN kpi_title VARCHAR(500) NULL');
        }
    }
};
