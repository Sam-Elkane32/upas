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
        // Update forms table
        if (Schema::hasTable('forms') && Schema::hasColumn('forms', 'kpi_title')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->string('kpi_title', 300)->change();
            });
        }

        // Update templates table
        if (Schema::hasTable('templates') && Schema::hasColumn('templates', 'kpi_title')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->string('kpi_title', 300)->change();
            });
        }

        // Update submissions table
        if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'kpi_title')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->string('kpi_title', 300)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert forms table
        if (Schema::hasTable('forms') && Schema::hasColumn('forms', 'kpi_title')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->string('kpi_title', 255)->change();
            });
        }

        // Revert templates table
        if (Schema::hasTable('templates') && Schema::hasColumn('templates', 'kpi_title')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->string('kpi_title', 255)->change();
            });
        }

        // Revert submissions table
        if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'kpi_title')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->string('kpi_title', 255)->nullable()->change();
            });
        }
    }
};
