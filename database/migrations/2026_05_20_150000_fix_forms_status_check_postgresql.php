<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * App uses Unpublished/Published; legacy PostgreSQL check allowed Draft/Published only.
     */
    public function up(): void
    {
        if (!Schema::hasTable('forms') || !Schema::hasColumn('forms', 'status')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE forms DROP CONSTRAINT IF EXISTS forms_status_check');
            DB::table('forms')->where('status', 'Draft')->update(['status' => 'Unpublished']);
            DB::statement("ALTER TABLE forms ALTER COLUMN status TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE forms ALTER COLUMN status SET DEFAULT 'Unpublished'");
            DB::statement("ALTER TABLE forms ADD CONSTRAINT forms_status_check CHECK (status IN ('Unpublished', 'Published'))");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE forms MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Unpublished'");
        }
    }

    public function down(): void
    {
        // Non-destructive for production data.
    }
};
