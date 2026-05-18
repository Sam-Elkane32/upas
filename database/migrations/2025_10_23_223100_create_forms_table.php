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
        if (Schema::hasTable('forms')) {
            return;
        }

        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('form_id')->unique(); // Auto-generated form ID
            $table->string('title'); // Office/Unit Name
            $table->string('template_id'); // T1, T2, T3, T4
            $table->string('strategic_goal'); // SG1-SG5
            $table->string('kra_title');
            $table->string('kpi_title');
            $table->string('responsible_unit');
            $table->string('campus_code');
            $table->json('form_schema'); // Customized schema for this form
            $table->boolean('is_published')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('template_id')->references('template_id')->on('templates');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('campus_code')->references('code')->on('campuses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};

