<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column already exists
        if (config('database.default') === 'sqlite') {
            $hasColumn = \Illuminate\Support\Facades\DB::select("PRAGMA table_info(templates)");
            $columnExists = collect($hasColumn)->contains(function ($column) {
                return $column->name === 'form_id';
            });
            
            if (!$columnExists) {
                \Illuminate\Support\Facades\DB::statement('ALTER TABLE templates ADD COLUMN form_id INTEGER NULL');
            }
        } else {
            if (!Schema::hasColumn('templates', 'form_id')) {
                Schema::table('templates', function (Blueprint $table) {
                    $table->unsignedBigInteger('form_id')->nullable()->after('id');
                    $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            // SQLite doesn't support dropping columns easily, would need to recreate table
            // For now, we'll just leave it as nullable
            \Illuminate\Support\Facades\DB::statement('CREATE TABLE templates_backup AS SELECT id, sg_code, template_code, kra_title, kpi_title, fields_json, status, created_by, campus_code, created_at, updated_at FROM templates');
            \Illuminate\Support\Facades\DB::statement('DROP TABLE templates');
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE templates_backup RENAME TO templates');
        } else {
            Schema::table('templates', function (Blueprint $table) {
                $table->dropForeign(['form_id']);
                $table->dropColumn('form_id');
            });
        }
    }
};
