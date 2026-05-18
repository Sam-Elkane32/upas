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

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE forms MODIFY COLUMN kpi_title LONGTEXT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'kpi_title')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE forms MODIFY COLUMN kpi_title VARCHAR(500) NULL');
    }
};
