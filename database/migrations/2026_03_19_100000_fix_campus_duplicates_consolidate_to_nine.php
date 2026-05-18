<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function hasColumn(string $table, string $col): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $col);
    }
    /**
     * Consolidate duplicate campuses to exactly 9 PSU campuses.
     * Removes short-code duplicates (LING, ALAM, etc.) when full codes exist (LINGAYEN, ALAMINOS, etc.).
     */
    public function up(): void
    {
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';
        if ($sqlite) {
            DB::statement('PRAGMA foreign_keys = OFF');
        }
        try {
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

        // Step 1: Update references in other tables from short codes to full codes
        foreach ($codeMapping as $oldCode => $newCode) {
            foreach (['users', 'forms', 'templates', 'submissions', 'form_submissions'] as $table) {
                if ($this->hasTable($table) && $this->hasColumn($table, 'campus_code')) {
                    DB::table($table)->where('campus_code', $oldCode)->update(['campus_code' => $newCode]);
                }
            }
        }

        // Step 2: Delete short-code campuses (duplicates)
        foreach (array_keys($codeMapping) as $oldCode) {
            DB::table('campuses')->where('code', $oldCode)->delete();
        }

        // Step 3: Ensure exactly 9 campuses with correct names
        $canonical = [
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

        foreach ($canonical as $c) {
            $existing = DB::table('campuses')->where('code', $c['code'])->first();
            $data = array_merge($c, ['is_active' => true, 'updated_at' => now()]);
            if ($existing) {
                unset($data['code']);
                DB::table('campuses')->where('code', $c['code'])->update($data);
            } else {
                $data['created_at'] = now();
                DB::table('campuses')->insert($data);
            }
        }

        // Step 4: Remove any other campuses not in the canonical list
        $validCodes = array_column($canonical, 'code');
        DB::table('campuses')->whereNotIn('code', $validCodes)->delete();
        } finally {
            if ($sqlite) {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    public function down(): void
    {
        // Irreversible - do not reverse
    }
};
