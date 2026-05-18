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
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';
        if ($sqlite) {
            DB::statement('PRAGMA foreign_keys = OFF');
        }
        try {
        // Mapping of old codes to new full codes
        $codeMapping = [
            'LING' => 'LINGAYEN',
            'ALAM' => 'ALAMINOS',
            'URDA' => 'URDANETA',
            'BAYA' => 'BAYAMBANG',
            'INFA' => 'INFANTA',
            'SANC' => 'SANCARLOS',
            'ASIN' => 'ASINGAN',
            'BINM' => 'BINMALEY',
            'STAM' => 'STAMARIA',
        ];

        // Step 1: Update campus codes in campuses table and handle duplicates
        foreach ($codeMapping as $oldCode => $newCode) {
            $campusWithOldCode = DB::table('campuses')->where('code', $oldCode)->first();
            $campusWithNewCode = DB::table('campuses')->where('code', $newCode)->first();
            
            if ($campusWithOldCode) {
                if ($campusWithNewCode) {
                    // Both exist - delete the old one (duplicate)
                    DB::table('campuses')->where('code', $oldCode)->delete();
                } else {
                    // Only old exists - update to new code
                    DB::table('campuses')->where('code', $oldCode)->update(['code' => $newCode]);
                }
            }
        }

        // Step 2: Update users that reference old short codes
        foreach ($codeMapping as $oldCode => $newCode) {
            // Update campus_code field
            DB::table('users')
                ->where('campus_code', $oldCode)
                ->update(['campus_code' => $newCode]);
            
            // Get the correct campus name for the new code
            $campusName = DB::table('campuses')->where('code', $newCode)->value('name');
            if ($campusName) {
                // Update users with the correct campus name
                DB::table('users')
                    ->where('campus_code', $newCode)
                    ->update(['campus' => $campusName]);
            }
        }

        // Now ensure all 9 campuses exist with full codes
        $campuses = [
            ['name' => 'PSU Lingayen Campus', 'code' => 'LINGAYEN', 'location' => 'Lingayen, Pangasinan', 'description' => 'Main Campus'],
            ['name' => 'PSU Alaminos Campus', 'code' => 'ALAMINOS', 'location' => 'Alaminos City, Pangasinan', 'description' => 'Alaminos Campus'],
            ['name' => 'PSU Urdaneta Campus', 'code' => 'URDANETA', 'location' => 'Urdaneta City, Pangasinan', 'description' => 'Urdaneta Campus'],
            ['name' => 'PSU Bayambang Campus', 'code' => 'BAYAMBANG', 'location' => 'Bayambang, Pangasinan', 'description' => 'Bayambang Campus'],
            ['name' => 'PSU Infanta Campus', 'code' => 'INFANTA', 'location' => 'Infanta, Pangasinan', 'description' => 'Infanta Campus'],
            ['name' => 'PSU San Carlos Campus', 'code' => 'SANCARLOS', 'location' => 'San Carlos City, Pangasinan', 'description' => 'San Carlos Campus'],
            ['name' => 'PSU Asingan Campus', 'code' => 'ASINGAN', 'location' => 'Asingan, Pangasinan', 'description' => 'Asingan Campus'],
            ['name' => 'PSU Binmaley Campus', 'code' => 'BINMALEY', 'location' => 'Binmaley, Pangasinan', 'description' => 'Binmaley Campus'],
            ['name' => 'PSU Sta. Maria Campus', 'code' => 'STAMARIA', 'location' => 'Sta. Maria, Pangasinan', 'description' => 'Sta. Maria Campus'],
        ];

        foreach ($campuses as $campus) {
            $existing = DB::table('campuses')->where('code', $campus['code'])->first();
            
            if ($existing) {
                // Update existing campus
                DB::table('campuses')->where('code', $campus['code'])->update(
                    array_merge($campus, [
                        'is_active' => true,
                        'updated_at' => now(),
                    ])
                );
            } else {
                // Insert new campus
                DB::table('campuses')->insert(
                    array_merge($campus, [
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }

        // Update any other tables that reference campus_code
        // Update forms table if it exists and has campus_code column
        if (Schema::hasTable('forms') && Schema::hasColumn('forms', 'campus_code')) {
            foreach ($codeMapping as $oldCode => $newCode) {
                DB::table('forms')
                    ->where('campus_code', $oldCode)
                    ->update(['campus_code' => $newCode]);
            }
        }

        // Update form_submissions table if it exists and has campus_code column
        if (Schema::hasTable('form_submissions') && Schema::hasColumn('form_submissions', 'campus_code')) {
            foreach ($codeMapping as $oldCode => $newCode) {
                DB::table('form_submissions')
                    ->where('campus_code', $oldCode)
                    ->update(['campus_code' => $newCode]);
            }
        }
        } finally {
            if ($sqlite) {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't reverse as this is a data cleanup migration
    }
};
