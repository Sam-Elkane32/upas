<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make forms.campus_code nullable.
     * Campus is assigned when Super Admin creates/edits a template and selects Planning Coordinator(s).
     */
    public function up(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'campus_code')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->dropForeign(['campus_code']);
        });

        DB::statement('ALTER TABLE forms MODIFY COLUMN campus_code VARCHAR(50) NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'campus_code')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE forms MODIFY COLUMN campus_code VARCHAR(50) NOT NULL DEFAULT "LINGAYEN"');

        Schema::table('forms', function (Blueprint $table) {
            $table->foreign('campus_code')->references('code')->on('campuses')->onDelete('cascade');
        });
    }
};
