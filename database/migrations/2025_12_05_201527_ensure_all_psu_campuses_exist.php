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
        // Ensure campuses table exists
        if (!Schema::hasTable('campuses')) {
            Schema::create('campuses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('location')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Insert all 9 PSU campuses (will update if exists, insert if not)
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't remove campuses as they may have data dependencies
        // If you need to remove, do it manually
    }
};
