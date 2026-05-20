<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Form model columns to forms table if missing (database-agnostic).
     */
    public function up(): void
    {
        if (!Schema::hasTable('forms')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $columns = [
                'form_title' => fn () => $table->string('form_title')->nullable(),
                'division' => fn () => $table->string('division')->nullable(),
                'sg_code' => fn () => $table->string('sg_code')->nullable(),
                'strategic_goal' => fn () => $table->string('strategic_goal')->nullable(),
                'kra_title' => fn () => $table->string('kra_title')->nullable(),
                'kpi_title' => fn () => $table->text('kpi_title')->nullable(),
                'responsible_unit' => fn () => $table->text('responsible_unit')->nullable(),
                'kra_kpi_data' => fn () => $table->json('kra_kpi_data')->nullable(),
                'target_q1' => fn () => $table->decimal('target_q1', 10, 2)->default(0),
                'target_q2' => fn () => $table->decimal('target_q2', 10, 2)->default(0),
                'target_q3' => fn () => $table->decimal('target_q3', 10, 2)->default(0),
                'target_q4' => fn () => $table->decimal('target_q4', 10, 2)->default(0),
                'target_total' => fn () => $table->decimal('target_total', 10, 2)->default(0),
                'template_code' => fn () => $table->string('template_code')->nullable(),
                'status' => fn () => $table->string('status')->default('Unpublished'),
                'created_by' => fn () => $table->unsignedBigInteger('created_by')->nullable(),
                'campus_code' => fn () => $table->string('campus_code')->nullable(),
            ];

            foreach ($columns as $name => $add) {
                if (!Schema::hasColumn('forms', $name)) {
                    $add();
                }
            }
        });

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
