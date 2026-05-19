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
        // Drop the existing templates table if it exists
        Schema::dropIfExists('templates');
        
        // Create the templates table with the correct structure
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('sg_code')->nullable(); // Strategic Goal Code
            $table->string('template_code')->unique(); // T1-T4
            $table->string('kra_title');
            $table->string('kpi_title');
            $table->longText('fields_json'); // Template field structure
            $table->enum('status', ['Draft', 'Published'])->default('Draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('campus_code');
            $table->timestamps();
            $table->foreign('campus_code')->references('code')->on('campuses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
