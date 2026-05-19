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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('submission_id')->nullable();
            $table->string('accomp_term')->nullable(); // Year/Semester
            $table->string('sdp_ref')->nullable(); // SDP Reference
            $table->decimal('accomp_q1', 10, 2)->default(0);
            $table->decimal('accomp_q2', 10, 2)->default(0);
            $table->decimal('accomp_q3', 10, 2)->default(0);
            $table->decimal('accomp_q4', 10, 2)->default(0);
            $table->decimal('accomp_total', 10, 2)->default(0);
            $table->decimal('variance', 10, 2)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->string('rating')->nullable(); // Descriptive Rating
            $table->text('remarks')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Returned'])->default('Pending');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
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


































