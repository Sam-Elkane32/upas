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
        Schema::create('strategic_goals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('category', [
                'Academic Excellence', 
                'Research & Innovation', 
                'Community Extension', 
                'Administration & Governance'
            ]);
            $table->year('target_year');
            $table->enum('status', ['Active', 'Completed', 'On Hold', 'Cancelled'])->default('Active');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->decimal('weight_percentage', 5, 2)->default(0); // For calculating overall progress
            $table->text('success_indicators')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategic_goals');
    }
};
