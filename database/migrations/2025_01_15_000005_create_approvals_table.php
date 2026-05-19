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
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
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
            $table->foreignId('validated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
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
