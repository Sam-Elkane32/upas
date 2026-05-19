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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('form_title');
            $table->string('sg_code'); // SG1-SG5
            $table->string('kra_title');
            $table->string('kpi_title');
            $table->string('responsible_unit');
            $table->decimal('target_q1', 10, 2)->default(0);
            $table->decimal('target_q2', 10, 2)->default(0);
            $table->decimal('target_q3', 10, 2)->default(0);
            $table->decimal('target_q4', 10, 2)->default(0);
            $table->decimal('target_total', 10, 2)->default(0);
            $table->string('template_code')->nullable(); // T1-T4
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
        Schema::dropIfExists('forms');
    }
};


































