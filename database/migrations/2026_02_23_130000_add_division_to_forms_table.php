<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('forms', function (Blueprint $table) {
                $table->string('division')->nullable();
            });

            return;
        }

        $exists = DB::selectOne("SHOW COLUMNS FROM forms LIKE 'division'");
        if (!$exists) {
            DB::statement("ALTER TABLE forms ADD COLUMN division VARCHAR(255) NULL AFTER form_title");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'division')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('forms', function (Blueprint $table) {
                $table->dropColumn('division');
            });

            return;
        }

        DB::statement('ALTER TABLE forms DROP COLUMN division');
    }
};
