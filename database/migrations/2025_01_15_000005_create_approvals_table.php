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
        // Replace the approvals table from 2025_01_15_000003 (different schema, no submissions FK).
        Schema::dropIfExists('approvals');

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_id')->unique(); // UUID for approval record
            $table->string('submission_id'); // Linked to submissions.id
            $table->string('accomp_term'); // Year or semester covered
            $table->string('sdp_ref')->nullable(); // Strategic Development Plan ref
            $table->decimal('target_q1', 10, 2)->default(0);
            $table->decimal('target_q2', 10, 2)->default(0);
            $table->decimal('target_q3', 10, 2)->default(0);
            $table->decimal('target_q4', 10, 2)->default(0);
            $table->decimal('target_total', 10, 2)->default(0); // Auto: Q1+Q2+Q3+Q4
            $table->decimal('accomp_q1', 10, 2)->default(0);
            $table->decimal('accomp_q2', 10, 2)->default(0);
            $table->decimal('accomp_q3', 10, 2)->default(0);
            $table->decimal('accomp_q4', 10, 2)->default(0);
            $table->decimal('accomp_total', 10, 2)->default(0); // Auto: Q1+Q2+Q3+Q4
            $table->decimal('variance', 10, 2)->default(0); // Auto: accomp_total - target_total
            $table->decimal('rate', 5, 2)->default(0); // Auto: (accomp_total ÷ target_total) × 100
            $table->enum('rating', ['Outstanding', 'Very Satisfactory', 'Satisfactory', 'Fair', 'Needs Improvement'])->default('Needs Improvement');
            $table->text('remarks')->nullable(); // Admin comments
            $table->string('validated_by'); // Auto — Campus Admin ID
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('submission_id')->references('id')->on('submissions')->onDelete('cascade');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate approvals per submission
            $table->unique('submission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
