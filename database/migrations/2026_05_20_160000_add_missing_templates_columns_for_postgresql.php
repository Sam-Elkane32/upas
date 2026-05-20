<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add templates columns required by Form Details and Super Admin (PostgreSQL / partial migrate paths).
     */
    public function up(): void
    {
        if (!Schema::hasTable('templates')) {
            return;
        }

        Schema::table('templates', function (Blueprint $table) {
            if (!Schema::hasColumn('templates', 'campus_codes')) {
                $table->text('campus_codes')->nullable();
            }
            if (!Schema::hasColumn('templates', 'is_locked')) {
                $table->boolean('is_locked')->default(false);
            }
            if (!Schema::hasColumn('templates', 'locked_at')) {
                $table->timestamp('locked_at')->nullable();
            }
            if (!Schema::hasColumn('templates', 'locked_by')) {
                $table->unsignedBigInteger('locked_by')->nullable();
            }
            if (!Schema::hasColumn('templates', 'lock_reason')) {
                $table->text('lock_reason')->nullable();
            }
        });

        if (Schema::hasTable('users') && Schema::hasColumn('templates', 'locked_by')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'pgsql') {
                DB::statement('
                    DO $$
                    BEGIN
                        IF NOT EXISTS (
                            SELECT 1 FROM pg_constraint WHERE conname = \'templates_locked_by_foreign\'
                        ) THEN
                            ALTER TABLE templates
                                ADD CONSTRAINT templates_locked_by_foreign
                                FOREIGN KEY (locked_by) REFERENCES users(id) ON DELETE SET NULL;
                        END IF;
                    END $$;
                ');
            } else {
                Schema::table('templates', function (Blueprint $table) {
                    $table->foreign('locked_by')->references('id')->on('users')->nullOnDelete();
                });
            }
        }

        if (Schema::hasColumn('templates', 'campus_codes') && Schema::hasColumn('templates', 'campus_code')) {
            $rows = DB::table('templates')
                ->whereNotNull('campus_code')
                ->where(function ($q) {
                    $q->whereNull('campus_codes')
                        ->orWhere('campus_codes', '')
                        ->orWhere('campus_codes', '[]');
                })
                ->get(['id', 'campus_code']);

            foreach ($rows as $row) {
                DB::table('templates')->where('id', $row->id)->update([
                    'campus_codes' => json_encode([strtoupper(trim((string) $row->campus_code))]),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive for production data.
    }
};
