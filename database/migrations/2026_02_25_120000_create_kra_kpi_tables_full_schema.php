<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create KRA and KPI tables with full schema: KRA, KPI, responsible work unit per KPI, target Q1–Q4 per KPI.
     * Creates tables if missing; adds missing columns if tables already exist.
     */
    public function up(): void
    {
        // --- k_r_a_s (Key Result Areas) ---
        if (!Schema::hasTable('k_r_a_s')) {
            Schema::create('k_r_a_s', function (Blueprint $table) {
                $table->id();
                if (Schema::hasTable('strategic_goals')) {
                    $table->unsignedBigInteger('strategic_goal_id')->nullable();
                    $table->foreign('strategic_goal_id')->references('id')->on('strategic_goals')->onDelete('set null');
                } else {
                    $table->unsignedBigInteger('strategic_goal_id')->nullable();
                }
                $table->string('code', 50)->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                if (Schema::hasTable('users')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                } else {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
                $table->timestamps();
            });
        } else {
            Schema::table('k_r_a_s', function (Blueprint $table) {
                if (!Schema::hasColumn('k_r_a_s', 'strategic_goal_id')) {
                    $table->unsignedBigInteger('strategic_goal_id')->nullable()->after('id');
                    if (Schema::hasTable('strategic_goals')) {
                        $table->foreign('strategic_goal_id')->references('id')->on('strategic_goals')->onDelete('set null');
                    }
                }
                if (!Schema::hasColumn('k_r_a_s', 'code')) $table->string('code', 50)->nullable()->after('strategic_goal_id');
                if (!Schema::hasColumn('k_r_a_s', 'title')) $table->string('title')->nullable()->after('code');
                if (!Schema::hasColumn('k_r_a_s', 'description')) $table->text('description')->nullable()->after('title');
                if (!Schema::hasColumn('k_r_a_s', 'is_active')) $table->boolean('is_active')->default(true)->after('description');
                if (!Schema::hasColumn('k_r_a_s', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
                    if (Schema::hasTable('users')) {
                        $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                    }
                }
            });
        }

        // --- k_p_i_s (Key Performance Indicators: KPI, responsible work unit, target Q1–Q4) ---
        if (!Schema::hasTable('k_p_i_s')) {
            Schema::create('k_p_i_s', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kra_id')->nullable();
                $table->string('code', 50)->nullable();
                $table->string('title', 500)->nullable();
                $table->text('description')->nullable();
                $table->string('campus_code', 50)->nullable();
                $table->string('responsible_unit', 255)->nullable()->comment('Responsible work unit for this KPI');
                $table->string('measurement_unit', 100)->nullable()->default('Units');
                $table->decimal('target_q1', 12, 2)->default(0);
                $table->decimal('target_q2', 12, 2)->default(0);
                $table->decimal('target_q3', 12, 2)->default(0);
                $table->decimal('target_q4', 12, 2)->default(0);
                $table->decimal('target_total', 12, 2)->default(0);
                $table->decimal('accomplishment_q1', 12, 2)->default(0);
                $table->decimal('accomplishment_q2', 12, 2)->default(0);
                $table->decimal('accomplishment_q3', 12, 2)->default(0);
                $table->decimal('accomplishment_q4', 12, 2)->default(0);
                $table->decimal('accomplishment_total', 12, 2)->default(0);
                $table->decimal('variance', 12, 2)->default(0);
                $table->decimal('rate_of_accomplishment', 5, 2)->default(0);
                $table->string('descriptive_rating', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                if (Schema::hasTable('k_r_a_s')) {
                    $table->foreign('kra_id')->references('id')->on('k_r_a_s')->onDelete('cascade');
                }
                if (Schema::hasTable('campuses')) {
                    $table->foreign('campus_code')->references('code')->on('campuses')->onDelete('set null');
                }
                if (Schema::hasTable('users')) {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        } else {
            Schema::table('k_p_i_s', function (Blueprint $table) {
                if (!Schema::hasColumn('k_p_i_s', 'kra_id')) {
                    $table->unsignedBigInteger('kra_id')->nullable()->after('id');
                    if (Schema::hasTable('k_r_a_s')) {
                        $table->foreign('kra_id')->references('id')->on('k_r_a_s')->onDelete('cascade');
                    }
                }
                if (!Schema::hasColumn('k_p_i_s', 'code')) $table->string('code', 50)->nullable()->after('kra_id');
                if (!Schema::hasColumn('k_p_i_s', 'title')) $table->string('title', 500)->nullable()->after('code');
                if (!Schema::hasColumn('k_p_i_s', 'description')) $table->text('description')->nullable()->after('title');
                if (!Schema::hasColumn('k_p_i_s', 'campus_code')) {
                    $table->string('campus_code', 50)->nullable()->after('description');
                    if (Schema::hasTable('campuses')) {
                        $table->foreign('campus_code')->references('code')->on('campuses')->onDelete('set null');
                    }
                }
                if (!Schema::hasColumn('k_p_i_s', 'responsible_unit')) {
                    $table->string('responsible_unit', 255)->nullable()->after('campus_code')->comment('Responsible work unit');
                }
                if (!Schema::hasColumn('k_p_i_s', 'measurement_unit')) $table->string('measurement_unit', 100)->nullable()->default('Units')->after('responsible_unit');
                if (!Schema::hasColumn('k_p_i_s', 'target_q1')) $table->decimal('target_q1', 12, 2)->default(0)->after('measurement_unit');
                if (!Schema::hasColumn('k_p_i_s', 'target_q2')) $table->decimal('target_q2', 12, 2)->default(0)->after('target_q1');
                if (!Schema::hasColumn('k_p_i_s', 'target_q3')) $table->decimal('target_q3', 12, 2)->default(0)->after('target_q2');
                if (!Schema::hasColumn('k_p_i_s', 'target_q4')) $table->decimal('target_q4', 12, 2)->default(0)->after('target_q3');
                if (!Schema::hasColumn('k_p_i_s', 'target_total')) $table->decimal('target_total', 12, 2)->default(0)->after('target_q4');
                if (!Schema::hasColumn('k_p_i_s', 'accomplishment_q1')) $table->decimal('accomplishment_q1', 12, 2)->default(0)->after('target_total');
                if (!Schema::hasColumn('k_p_i_s', 'accomplishment_q2')) $table->decimal('accomplishment_q2', 12, 2)->default(0)->after('accomplishment_q1');
                if (!Schema::hasColumn('k_p_i_s', 'accomplishment_q3')) $table->decimal('accomplishment_q3', 12, 2)->default(0)->after('accomplishment_q2');
                if (!Schema::hasColumn('k_p_i_s', 'accomplishment_q4')) $table->decimal('accomplishment_q4', 12, 2)->default(0)->after('accomplishment_q3');
                if (!Schema::hasColumn('k_p_i_s', 'accomplishment_total')) $table->decimal('accomplishment_total', 12, 2)->default(0)->after('accomplishment_q4');
                if (!Schema::hasColumn('k_p_i_s', 'variance')) $table->decimal('variance', 12, 2)->default(0)->after('accomplishment_total');
                if (!Schema::hasColumn('k_p_i_s', 'rate_of_accomplishment')) $table->decimal('rate_of_accomplishment', 5, 2)->default(0)->after('variance');
                if (!Schema::hasColumn('k_p_i_s', 'descriptive_rating')) $table->string('descriptive_rating', 100)->nullable()->after('rate_of_accomplishment');
                if (!Schema::hasColumn('k_p_i_s', 'is_active')) $table->boolean('is_active')->default(true)->after('descriptive_rating');
                if (!Schema::hasColumn('k_p_i_s', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
                    if (Schema::hasTable('users')) {
                        $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('k_p_i_s');
        Schema::dropIfExists('k_r_a_s');
    }
};
