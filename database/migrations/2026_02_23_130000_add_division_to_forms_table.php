<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add division column to forms table if missing.
     */
    public function up(): void
    {
        if (!Schema::hasTable('forms') || Schema::hasColumn('forms', 'division')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->string('division')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'division')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('division');
        });
    }
};
