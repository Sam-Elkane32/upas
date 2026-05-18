<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Increase templates.kpi_title length to LONGTEXT (MySQL 5.6 safe).
     * KPI titles can be multi-line and exceed 300 chars.
     */
    public function up(): void
    {
        if (!Schema::hasTable('templates') || !Schema::hasColumn('templates', 'kpi_title')) {
            return;
        }

        // SQLite doesn't support MODIFY COLUMN the same way; skip in that case.
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE templates MODIFY COLUMN kpi_title LONGTEXT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('templates') || !Schema::hasColumn('templates', 'kpi_title')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE templates MODIFY COLUMN kpi_title VARCHAR(300) NULL");
    }
};

