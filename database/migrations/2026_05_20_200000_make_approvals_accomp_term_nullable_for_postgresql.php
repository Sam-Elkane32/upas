<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * QA approve flow does not require accomp_term; allow null on partial PostgreSQL schemas.
     */
    public function up(): void
    {
        if (! Schema::hasTable('approvals') || ! Schema::hasColumn('approvals', 'accomp_term')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE approvals ALTER COLUMN accomp_term DROP NOT NULL');
    }

    public function down(): void
    {
        // Non-destructive for production data.
    }
};
