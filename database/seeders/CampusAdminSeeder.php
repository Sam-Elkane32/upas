<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampusAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert sample templates
        DB::table('templates')->insert([
            [
                'template_code' => 'T1',
                'kra_title' => 'Basic Accomplishment Tracking',
                'kpi_title' => 'Number of completed activities',
                'fields_json' => json_encode([
                    'fields' => [
                        ['label' => 'Campus', 'type' => 'text', 'required' => true],
                        ['label' => 'Quarter', 'type' => 'dropdown', 'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q'], 'required' => true],
                        ['label' => 'Activity Title', 'type' => 'text', 'required' => true],
                        ['label' => 'Target Output', 'type' => 'number', 'required' => true],
                        ['label' => 'Actual Output', 'type' => 'number', 'required' => true],
                        ['label' => 'Remarks', 'type' => 'textarea', 'required' => false]
                    ]
                ]),
                'status' => 'Published',
                'created_by' => '1',
                'campus_code' => 'LINGAYEN',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}


































