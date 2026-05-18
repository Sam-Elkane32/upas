<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('approvals', 'approval_id')) {
                $table->string('approval_id')->unique()->after('id');
            }
            if (!Schema::hasColumn('approvals', 'submission_id')) {
                $table->string('submission_id')->after('approval_id');
            }
            if (!Schema::hasColumn('approvals', 'accomp_term')) {
                $table->string('accomp_term')->after('submission_id');
            }
            if (!Schema::hasColumn('approvals', 'sdp_ref')) {
                $table->string('sdp_ref')->nullable()->after('accomp_term');
            }
            if (!Schema::hasColumn('approvals', 'target_q1')) {
                $table->decimal('target_q1', 10, 2)->default(0)->after('sdp_ref');
            }
            if (!Schema::hasColumn('approvals', 'target_q2')) {
                $table->decimal('target_q2', 10, 2)->default(0)->after('target_q1');
            }
            if (!Schema::hasColumn('approvals', 'target_q3')) {
                $table->decimal('target_q3', 10, 2)->default(0)->after('target_q2');
            }
            if (!Schema::hasColumn('approvals', 'target_q4')) {
                $table->decimal('target_q4', 10, 2)->default(0)->after('target_q3');
            }
            if (!Schema::hasColumn('approvals', 'target_total')) {
                $table->decimal('target_total', 10, 2)->default(0)->after('target_q4');
            }
            if (!Schema::hasColumn('approvals', 'accomp_q1')) {
                $table->decimal('accomp_q1', 10, 2)->default(0)->after('target_total');
            }
            if (!Schema::hasColumn('approvals', 'accomp_q2')) {
                $table->decimal('accomp_q2', 10, 2)->default(0)->after('accomp_q1');
            }
            if (!Schema::hasColumn('approvals', 'accomp_q3')) {
                $table->decimal('accomp_q3', 10, 2)->default(0)->after('accomp_q2');
            }
            if (!Schema::hasColumn('approvals', 'accomp_q4')) {
                $table->decimal('accomp_q4', 10, 2)->default(0)->after('accomp_q3');
            }
            if (!Schema::hasColumn('approvals', 'accomp_total')) {
                $table->decimal('accomp_total', 10, 2)->default(0)->after('accomp_q4');
            }
            if (!Schema::hasColumn('approvals', 'variance')) {
                $table->decimal('variance', 10, 2)->default(0)->after('accomp_total');
            }
            if (!Schema::hasColumn('approvals', 'rate')) {
                $table->decimal('rate', 5, 2)->default(0)->after('variance');
            }
            if (!Schema::hasColumn('approvals', 'rating')) {
                $table->enum('rating', ['Outstanding', 'Very Satisfactory', 'Satisfactory', 'Fair', 'Needs Improvement'])->default('Needs Improvement')->after('rate');
            }
            if (!Schema::hasColumn('approvals', 'remarks')) {
                $table->text('remarks')->nullable()->after('rating');
            }
            if (!Schema::hasColumn('approvals', 'validated_by')) {
                $table->string('validated_by')->after('remarks');
            }
            if (!Schema::hasColumn('approvals', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only adds columns, so we don't need to reverse it
        // as it would break existing data
    }
};
