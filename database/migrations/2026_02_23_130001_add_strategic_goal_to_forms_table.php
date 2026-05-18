<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('forms', function (Blueprint $table) {
                $table->string('strategic_goal')->nullable();
            });

            return;
        }

        $exists = DB::selectOne("SHOW COLUMNS FROM forms LIKE 'strategic_goal'");
        if (!$exists) {
            DB::statement("ALTER TABLE forms ADD COLUMN strategic_goal VARCHAR(255) NULL AFTER sg_code");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'strategic_goal')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('forms', function (Blueprint $table) {
                $table->dropColumn('strategic_goal');
            });

            return;
        }

        DB::statement('ALTER TABLE forms DROP COLUMN strategic_goal');
    }
};
