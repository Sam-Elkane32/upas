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
        Schema::create('key_performance_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategic_goal_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('measurement_type', ['Percentage', 'Number', 'Currency', 'Score']);
            $table->decimal('target_value', 15, 2);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->string('unit_of_measure')->nullable(); // e.g., '%', 'students', 'PHP', 'points'
            $table->enum('frequency', ['Monthly', 'Quarterly', 'Annually']);
            $table->date('deadline');
            $table->enum('status', ['Not Started', 'In Progress', 'Achieved', 'Overdue'])->default('Not Started');
            $table->text('calculation_method')->nullable();
            $table->json('quarterly_targets')->nullable(); // Q1, Q2, Q3, Q4 targets
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_performance_indicators');
    }
};
