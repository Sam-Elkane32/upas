<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure campuses, departments, and users table have columns needed for DatabaseSeeder.
     */
    public function up(): void
    {
        // Campuses (required by User seeder - campus codes)
        if (!Schema::hasTable('campuses')) {
            Schema::create('campuses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 191)->unique();
                $table->string('location')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Departments (required by User seeder - department id)
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 191)->unique();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('head_user_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['sqlite', 'pgsql'], true) && Schema::hasTable('users')) {
            $userColumnAdds = [
                ['employee_id', fn (Blueprint $t) => $t->string('employee_id')->nullable()],
                ['department', fn (Blueprint $t) => $t->unsignedBigInteger('department')->nullable()],
                ['position', fn (Blueprint $t) => $t->string('position')->nullable()],
                ['role', fn (Blueprint $t) => $t->string('role', 50)->default('creator_editor')],
                ['campus', fn (Blueprint $t) => $t->string('campus')->nullable()],
                ['campus_code', fn (Blueprint $t) => $t->string('campus_code', 50)->nullable()],
                ['phone_number', fn (Blueprint $t) => $t->string('phone_number')->nullable()],
                ['is_active', fn (Blueprint $t) => $t->boolean('is_active')->default(true)],
                ['is_approved', fn (Blueprint $t) => $t->boolean('is_approved')->default(false)],
                ['approved_at', fn (Blueprint $t) => $t->timestamp('approved_at')->nullable()],
                ['approved_by', fn (Blueprint $t) => $t->unsignedBigInteger('approved_by')->nullable()],
            ];
            foreach ($userColumnAdds as [$col, $fn]) {
                if (!Schema::hasColumn('users', $col)) {
                    Schema::table('users', function (Blueprint $table) use ($fn) {
                        $fn($table);
                    });
                }
            }

            return;
        }

        if ($driver !== 'mysql' || !Schema::hasTable('users')) {
            return;
        }

        // MySQL: raw SQL to avoid MySQL 5.6 schema introspection issues
        $addColumnIfMissing = function ($name, $definition) {
            $exists = DB::selectOne("SHOW COLUMNS FROM users LIKE '" . str_replace("'", "''", $name) . "'");
            if (!$exists) {
                DB::statement("ALTER TABLE users ADD COLUMN `" . str_replace('`', '``', $name) . "` {$definition}");
            }
        };

        $addColumnIfMissing('employee_id', "VARCHAR(255) NULL AFTER email");
        $addColumnIfMissing('department', "VARCHAR(255) NULL AFTER employee_id");
        $addColumnIfMissing('position', "VARCHAR(255) NULL AFTER department");
        $addColumnIfMissing('role', "VARCHAR(50) NOT NULL DEFAULT 'creator_editor' AFTER position");
        $addColumnIfMissing('campus', "VARCHAR(255) NULL AFTER role");
        $addColumnIfMissing('campus_code', "VARCHAR(50) NULL AFTER campus");
        $addColumnIfMissing('phone_number', "VARCHAR(255) NULL AFTER campus_code");
        $addColumnIfMissing('is_active', "TINYINT(1) NOT NULL DEFAULT 1 AFTER phone_number");
        $addColumnIfMissing('is_approved', "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");
        $addColumnIfMissing('approved_at', "TIMESTAMP NULL AFTER is_approved");
        $addColumnIfMissing('approved_by', "BIGINT UNSIGNED NULL AFTER approved_at");

        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'creator_editor'");
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // Optional: drop added columns; leave campuses/departments for data safety
    }
};
