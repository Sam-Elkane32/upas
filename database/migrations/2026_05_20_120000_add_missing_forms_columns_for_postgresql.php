<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add forms columns required by Super Admin Create Form.
     * Older migrations used MySQL-only SHOW COLUMNS / AFTER and no-op on PostgreSQL.
     */
    public function up(): void
    {
        if (!Schema::hasTable('forms')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'form_title')) {
                $table->string('form_title')->nullable();
            }
            if (!Schema::hasColumn('forms', 'division')) {
                $table->string('division')->nullable();
            }
            if (!Schema::hasColumn('forms', 'sg_code')) {
                $table->string('sg_code')->nullable();
            }
            if (!Schema::hasColumn('forms', 'strategic_goal')) {
                $table->string('strategic_goal')->nullable();
            }
            if (!Schema::hasColumn('forms', 'kra_title')) {
                $table->string('kra_title')->nullable();
            }
            if (!Schema::hasColumn('forms', 'kpi_title')) {
                $table->text('kpi_title')->nullable();
            }
            if (!Schema::hasColumn('forms', 'responsible_unit')) {
                $table->text('responsible_unit')->nullable();
            }
            if (!Schema::hasColumn('forms', 'kra_kpi_data')) {
                $table->json('kra_kpi_data')->nullable();
            }
            if (!Schema::hasColumn('forms', 'target_q1')) {
                $table->decimal('target_q1', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('forms', 'target_q2')) {
                $table->decimal('target_q2', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('forms', 'target_q3')) {
                $table->decimal('target_q3', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('forms', 'target_q4')) {
                $table->decimal('target_q4', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('forms', 'target_total')) {
                $table->decimal('target_total', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('forms', 'template_code')) {
                $table->string('template_code')->nullable();
            }
            if (!Schema::hasColumn('forms', 'status')) {
                $table->string('status')->default('Unpublished');
            }
        });

        // template_id: legacy schemas may use string; app expects unsignedBigInteger nullable.
        if (!Schema::hasColumn('forms', 'template_id')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Non-destructive for production data.
    }
};
