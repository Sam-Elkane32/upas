<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Base submissions table (later migrations extend it). Must exist before 2025_01_15_000005_create_approvals_table.
     */
    public function up(): void
    {
        if (Schema::hasTable('submissions')) {
            return;
        }

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submission_id', 191)->nullable()->unique();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('form_id')->nullable();
            $table->string('template_code')->nullable();
            $table->string('form_title')->nullable();
            $table->string('sg_code')->nullable();
            $table->string('kra_title')->nullable();
            $table->text('kpi_title')->nullable();
            $table->string('campus')->nullable();
            $table->string('campus_code')->nullable();
            $table->string('quarter')->nullable();
            $table->longText('table_data')->nullable();
            $table->string('status', 50)->nullable()->default('Unpublished');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->boolean('is_draft')->default(false);
            $table->unsignedInteger('draft_version')->default(1);
            $table->timestamp('last_draft_at')->nullable();
            $table->timestamps();

            $table->index('submitted_by');
            $table->index(['submitted_by', 'is_draft', 'status']);
            $table->index('template_id');
            $table->index('form_id');
            $table->index('status');

            if (Schema::hasTable('users')) {
                $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
            }
            if (Schema::hasTable('templates')) {
                $table->foreign('template_id')->references('id')->on('templates')->onDelete('set null');
            }
            if (Schema::hasTable('forms')) {
                $table->foreign('form_id')->references('id')->on('forms')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
