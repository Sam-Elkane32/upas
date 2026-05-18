<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add Responsible work unit and target quarter 1–4 (and target_total) to k_p_i_s table.
     * Uses SHOW COLUMNS to avoid Laravel's information_schema query (generation_expression) on older MySQL.
     */
    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    public function up(): void
    {
        if (!Schema::hasTable('k_p_i_s')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            if (!$this->hasColumn('k_p_i_s', 'responsible_unit')) {
                Schema::table('k_p_i_s', function (Blueprint $table) {
                    $table->string('responsible_unit')->nullable();
                });
            }
            if (!$this->hasColumn('k_p_i_s', 'target_q1')) {
                Schema::table('k_p_i_s', function (Blueprint $table) {
                    $table->decimal('target_q1', 12, 2)->default(0);
                    $table->decimal('target_q2', 12, 2)->default(0);
                    $table->decimal('target_q3', 12, 2)->default(0);
                    $table->decimal('target_q4', 12, 2)->default(0);
                    $table->decimal('target_total', 12, 2)->default(0);
                });
            }

            return;
        }

        $after = $this->hasColumn('k_p_i_s', 'campus_code') ? '`campus_code`' : '`description`';

        if (!$this->hasColumn('k_p_i_s', 'responsible_unit')) {
            DB::statement("ALTER TABLE k_p_i_s ADD COLUMN responsible_unit VARCHAR(255) NULL COMMENT 'Responsible work unit for this KPI' AFTER {$after}");
        }

        $targetAfter = $this->hasColumn('k_p_i_s', 'measurement_unit') ? '`measurement_unit`' : '`responsible_unit`';

        if (!$this->hasColumn('k_p_i_s', 'target_q1')) {
            DB::statement("ALTER TABLE k_p_i_s ADD COLUMN target_q1 DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER {$targetAfter}");
        }
        if (!$this->hasColumn('k_p_i_s', 'target_q2')) {
            DB::statement('ALTER TABLE k_p_i_s ADD COLUMN target_q2 DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER target_q1');
        }
        if (!$this->hasColumn('k_p_i_s', 'target_q3')) {
            DB::statement('ALTER TABLE k_p_i_s ADD COLUMN target_q3 DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER target_q2');
        }
        if (!$this->hasColumn('k_p_i_s', 'target_q4')) {
            DB::statement('ALTER TABLE k_p_i_s ADD COLUMN target_q4 DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER target_q3');
        }
        if (!$this->hasColumn('k_p_i_s', 'target_total')) {
            DB::statement('ALTER TABLE k_p_i_s ADD COLUMN target_total DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER target_q4');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('k_p_i_s')) {
            return;
        }
        $cols = [];
        if ($this->hasColumn('k_p_i_s', 'responsible_unit')) $cols[] = 'responsible_unit';
        if ($this->hasColumn('k_p_i_s', 'target_q1')) $cols[] = 'target_q1';
        if ($this->hasColumn('k_p_i_s', 'target_q2')) $cols[] = 'target_q2';
        if ($this->hasColumn('k_p_i_s', 'target_q3')) $cols[] = 'target_q3';
        if ($this->hasColumn('k_p_i_s', 'target_q4')) $cols[] = 'target_q4';
        if ($this->hasColumn('k_p_i_s', 'target_total')) $cols[] = 'target_total';
        if (!empty($cols)) {
            DB::statement('ALTER TABLE k_p_i_s DROP COLUMN ' . implode(', DROP COLUMN ', $cols));
        }
    }
};
