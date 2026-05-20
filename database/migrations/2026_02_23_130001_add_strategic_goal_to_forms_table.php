<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add strategic_goal column to forms table if missing.
     */
    public function up(): void
    {
        if (!Schema::hasTable('forms') || Schema::hasColumn('forms', 'strategic_goal')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->string('strategic_goal')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'strategic_goal')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('strategic_goal');
        });
    }
};
