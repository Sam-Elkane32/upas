<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, enum is stored as TEXT, so we can just add a check constraint
        // For MySQL, we need to modify the enum
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite stores enum as TEXT, so we just need to ensure the constraint allows 'view_only'
            // The actual constraint is handled at application level, so we can proceed
            // No schema changes needed for SQLite as it's just text
        } else {
            // For MySQL/PostgreSQL, modify the enum column
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'creator_editor', 'view_only') DEFAULT 'creator_editor'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't need rollback as it's just text
        } else {
            // For MySQL/PostgreSQL, revert the enum
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'creator_editor') DEFAULT 'creator_editor'");
        }
    }
};
