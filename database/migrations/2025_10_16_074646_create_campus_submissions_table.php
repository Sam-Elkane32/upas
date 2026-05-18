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
        Schema::create('campus_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses');
            $table->foreignId('user_id')->constrained('users');
            $table->string('strategic_goal');
            $table->string('kra'); // Key Result Area
            $table->string('kpi'); // Key Performance Indicator
            $table->decimal('target_value', 15, 2);
            $table->decimal('actual_value', 15, 2);
            $table->text('justification')->nullable();
            $table->string('file_path')->nullable();
            $table->string('google_drive_link')->nullable();
            $table->enum('status', ['pending', 'approved', 'returned'])->default('pending');
            $table->text('admin_remarks')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['campus_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campus_submissions');
    }
};