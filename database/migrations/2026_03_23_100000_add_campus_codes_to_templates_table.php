<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * templates.campus_codes was used in code but never migrated; scopeForCampus and Super Admin CRUD expect this column.
     */
    public function up(): void
    {
        if (!Schema::hasTable('templates')) {
            return;
        }

        if (!Schema::hasColumn('templates', 'campus_codes')) {
            Schema::table('templates', function (Blueprint $table) {
                // longText: compatible with MySQL versions that have no native JSON type (same idea as fields_json).
                $table->longText('campus_codes')->nullable()->after('campus_code');
            });
        }

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

    public function down(): void
    {
        if (Schema::hasTable('templates') && Schema::hasColumn('templates', 'campus_codes')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->dropColumn('campus_codes');
            });
        }
    }
};
