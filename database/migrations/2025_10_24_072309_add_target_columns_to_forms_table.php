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
        if (!Schema::hasTable('forms') || Schema::hasColumn('forms', 'target_q1')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->decimal('target_q1', 10, 2)->default(0)->after('responsible_unit');
            $table->decimal('target_q2', 10, 2)->default(0)->after('target_q1');
            $table->decimal('target_q3', 10, 2)->default(0)->after('target_q2');
            $table->decimal('target_q4', 10, 2)->default(0)->after('target_q3');
            $table->decimal('target_total', 10, 2)->default(0)->after('target_q4');
            $table->string('template_code')->nullable()->after('target_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['target_q1', 'target_q2', 'target_q3', 'target_q4', 'target_total', 'template_code']);
        });
    }
};
