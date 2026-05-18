<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create templates table (standalone for MySQL upas_db when full migrations not run).
     */
    public function up(): void
    {
        if (Schema::hasTable('templates')) {
            return;
        }

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id')->nullable();
            $table->string('sg_code')->nullable();
            $table->string('template_code');
            $table->string('kra_title');
            $table->string('kpi_title', 300);
            $table->longText('fields_json')->nullable(); // JSON stored as text (MySQL 5.6 has no JSON type)
            $table->string('status', 50)->default('Unpublished');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('campus_code', 50)->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->timestamps();

            $table->index('template_code');
            $table->index('status');
            $table->index('campus_code');
            if (Schema::hasTable('users')) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            }
            if (Schema::hasTable('campuses')) {
                $table->foreign('campus_code')->references('code')->on('campuses')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
