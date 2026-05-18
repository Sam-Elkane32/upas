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
        Schema::create('accomplishment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('objective')->nullable();
            
            // EOMS-specific fields from planning matrix
            $table->text('what_will_be_done')->nullable();
            $table->text('resources_needed')->nullable();
            $table->string('responsible_person')->nullable();
            $table->text('evaluation_method')->nullable();
            
            $table->date('target_date');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled', 'on_hold'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('category', ['teaching', 'research', 'extension', 'administrative', 'professional_development']);
            $table->integer('progress_percentage')->default(0);
            $table->timestamp('completion_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Link to Annual Operational Plan
            $table->foreignId('aop_id')->nullable()->constrained('annual_operational_plans')->nullOnDelete();
            
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index(['category', 'status']);
            $table->index('target_date');
            $table->index('aop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accomplishment_plans');
    }
};
