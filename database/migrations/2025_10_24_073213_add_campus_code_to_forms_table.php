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
        if (!Schema::hasTable('forms') || Schema::hasColumn('forms', 'campus_code')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->string('campus_code')->nullable()->after('created_by');
        });

        // Update existing records with default campus code
        DB::table('forms')->whereNull('campus_code')->update(['campus_code' => 'LINGAYEN']);

        // Make the column NOT NULL
        Schema::table('forms', function (Blueprint $table) {
            $table->string('campus_code')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('campus_code');
        });
    }
};
