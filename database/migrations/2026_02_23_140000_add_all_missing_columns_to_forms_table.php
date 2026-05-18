<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add all Form model columns to forms table if missing (MySQL 5.6 safe).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $addIfMissing = function ($column, $definition) {
            $exists = DB::selectOne("SHOW COLUMNS FROM forms LIKE '" . str_replace("'", "''", $column) . "'");
            if (!$exists) {
                DB::statement("ALTER TABLE forms ADD COLUMN `" . str_replace('`', '``', $column) . "` " . $definition);
            }
        };

        // Order and types matching Form fillable and INSERT in error
        $addIfMissing('kra_kpi_data', 'LONGTEXT NULL'); // JSON stored as text on MySQL 5.6
        $addIfMissing('target_q1', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
        $addIfMissing('target_q2', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
        $addIfMissing('target_q3', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
        $addIfMissing('target_q4', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
        $addIfMissing('target_total', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
        $addIfMissing('template_id', 'BIGINT UNSIGNED NULL');
        $addIfMissing('template_code', 'VARCHAR(191) NULL');
        $addIfMissing('status', "VARCHAR(50) NOT NULL DEFAULT 'Unpublished'");
        $addIfMissing('created_by', 'BIGINT UNSIGNED NULL');
        $addIfMissing('campus_code', 'VARCHAR(50) NULL');
        $addIfMissing('form_title', 'VARCHAR(255) NULL');
        $addIfMissing('division', 'VARCHAR(255) NULL');
        $addIfMissing('sg_code', 'VARCHAR(191) NULL');
        $addIfMissing('strategic_goal', 'VARCHAR(255) NULL');
        $addIfMissing('kra_title', 'VARCHAR(255) NULL');
        $addIfMissing('kpi_title', 'VARCHAR(500) NULL');
        $addIfMissing('responsible_unit', 'VARCHAR(255) NULL');
    }

    public function down(): void
    {
        // Optional: drop added columns one by one if needed
    }
};
