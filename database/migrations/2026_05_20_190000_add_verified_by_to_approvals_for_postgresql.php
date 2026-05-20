<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add approvals columns required by QA Coordinator approve workflow (PostgreSQL / partial migrate paths).
     */
    public function up(): void
    {
        if (! Schema::hasTable('approvals')) {
            return;
        }

        Schema::table('approvals', function (Blueprint $table) {
            if (! Schema::hasColumn('approvals', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable();
            }
            if (! Schema::hasColumn('approvals', 'validated_by')) {
                $table->unsignedBigInteger('validated_by')->nullable();
            }
            if (! Schema::hasColumn('approvals', 'validated_at')) {
                $table->timestamp('validated_at')->nullable();
            }
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql' || ! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('approvals', 'verified_by')) {
            DB::statement('
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = \'approvals_verified_by_foreign\'
                    ) THEN
                        ALTER TABLE approvals
                            ADD CONSTRAINT approvals_verified_by_foreign
                            FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL;
                    END IF;
                END $$;
            ');
        }

        if (Schema::hasColumn('approvals', 'validated_by')) {
            DB::statement('
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = \'approvals_validated_by_foreign\'
                    ) THEN
                        ALTER TABLE approvals
                            ADD CONSTRAINT approvals_validated_by_foreign
                            FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL;
                    END IF;
                END $$;
            ');
        }
    }

    public function down(): void
    {
        // Non-destructive for production data.
    }
};
