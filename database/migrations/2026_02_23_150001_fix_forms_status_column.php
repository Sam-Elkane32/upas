<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow 'Unpublished' in forms.status (app uses Unpublished/Published, column was Draft/Published).
     */
    public function up(): void
    {
        if (!Schema::hasTable('forms') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE forms MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Unpublished'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('forms') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE forms MODIFY COLUMN status ENUM('Draft', 'Published') NOT NULL DEFAULT 'Draft'");
    }
};
