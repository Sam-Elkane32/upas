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
        Schema::create('quarterly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->year('year');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
            $table->enum('status', ['Draft', 'Submitted', 'Under Review', 'Approved', 'Rejected'])->default('Draft');
            $table->date('submission_date')->nullable();
            $table->date('approval_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('executive_summary')->nullable();
            $table->json('achievements')->nullable(); // Major accomplishments
            $table->json('challenges')->nullable(); // Issues faced
            $table->json('kpi_results')->nullable(); // KPI performance data
            $table->text('recommendations')->nullable();
            $table->text('next_quarter_plans')->nullable();
            $table->decimal('overall_rating', 3, 2)->nullable(); // 1-5 scale
            $table->text('reviewer_comments')->nullable();
            $table->string('report_file_path')->nullable(); // PDF export path
            $table->timestamps();
            
            // Ensure unique quarterly reports per user/department
            $table->unique(['user_id', 'department_id', 'year', 'quarter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quarterly_reports');
    }
};
