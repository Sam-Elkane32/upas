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
        Schema::table('submissions', function (Blueprint $table) {
            // Add template-related columns if they don't exist
            if (!Schema::hasColumn('submissions', 'form_title')) {
                $table->string('form_title')->nullable()->after('template_code');
            }
            if (!Schema::hasColumn('submissions', 'sg_code')) {
                $table->string('sg_code')->nullable()->after('form_title');
            }
            if (!Schema::hasColumn('submissions', 'kra_title')) {
                $table->string('kra_title')->nullable()->after('sg_code');
            }
            if (!Schema::hasColumn('submissions', 'kpi_title')) {
                $table->string('kpi_title')->nullable()->after('kra_title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['form_title', 'sg_code', 'kra_title', 'kpi_title']);
        });
    }
};
