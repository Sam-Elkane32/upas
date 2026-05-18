<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManualTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert only baseline template directly into database
        DB::table('templates')->insert([
            [
                'template_id' => 'T1',
                'name' => 'Basic Accomplishment Form',
                'description' => 'Standard accomplishment reporting template for general KPIs',
                'schema' => json_encode([
                    'templateId' => 'T1',
                    'fields' => [
                        [
                            'label' => 'Campus',
                            'type' => 'text',
                            'required' => true,
                            'name' => 'campus'
                        ],
                        [
                            'label' => 'Quarter',
                            'type' => 'dropdown',
                            'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q'],
                            'required' => true,
                            'name' => 'quarter'
                        ],
                        [
                            'label' => 'Target Value',
                            'type' => 'number',
                            'required' => true,
                            'name' => 'target_value'
                        ],
                        [
                            'label' => 'Actual Value',
                            'type' => 'number',
                            'required' => true,
                            'name' => 'actual_value'
                        ],
                        [
                            'label' => 'Remarks',
                            'type' => 'textarea',
                            'required' => false,
                            'name' => 'remarks'
                        ]
                    ]
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}


































