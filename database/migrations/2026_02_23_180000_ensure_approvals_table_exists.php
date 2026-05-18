<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure approvals table exists (for MySQL/upas_db when table was never created).
     * Schema matches Approval model and QA Coordinator workflow.
     */
    public function up(): void
    {
        if (Schema::hasTable('approvals')) {
            return;
        }

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_id')->unique()->nullable();
            $table->unsignedBigInteger('submission_id');
            $table->string('accomp_term')->nullable();
            $table->string('sdp_ref')->nullable();
            $table->decimal('target_q1', 10, 2)->default(0);
            $table->decimal('target_q2', 10, 2)->default(0);
            $table->decimal('target_q3', 10, 2)->default(0);
            $table->decimal('target_q4', 10, 2)->default(0);
            $table->decimal('target_total', 10, 2)->default(0);
            $table->decimal('accomp_q1', 10, 2)->default(0);
            $table->decimal('accomp_q2', 10, 2)->default(0);
            $table->decimal('accomp_q3', 10, 2)->default(0);
            $table->decimal('accomp_q4', 10, 2)->default(0);
            $table->decimal('accomp_total', 10, 2)->default(0);
            $table->decimal('variance', 10, 2)->default(0);
            $table->decimal('rate', 5, 2)->default(0);
            $table->string('rating', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique('submission_id');
            $table->foreign('submission_id')->references('id')->on('submissions')->onDelete('cascade');
            if (Schema::hasTable('users')) {
                $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
