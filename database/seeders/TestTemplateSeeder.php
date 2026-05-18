<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;
use App\Models\User;

class TestTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        
        if (!$user) {
            $this->command->error('No users found. Please create a user first.');
            return;
        }

        // Create test template T1
        Template::create([
            'sg_code' => 'SG1',
            'template_code' => 'T1',
            'kra_title' => 'KRA1.1 Relevant Curriculum',
            'kpi_title' => 'Number of reviewed, enhanced, and CHED-approved curriculum',
            'fields_json' => [
                'fields' => [
                    [
                        'label' => 'Responsible Work Units',
                        'type' => 'text',
                        'required' => true
                    ],
                    [
                        'label' => 'Quarter',
                        'type' => 'dropdown',
                        'required' => true,
                        'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q']
                    ],
                    [
                        'label' => 'Activity Title',
                        'type' => 'text',
                        'required' => true
                    ],
                    [
                        'label' => 'Target Output',
                        'type' => 'text',
                        'required' => true
                    ],
                    [
                        'label' => 'Actual Output',
                        'type' => 'text',
                        'required' => true
                    ],
                    [
                        'label' => 'Remarks',
                        'type' => 'textarea',
                        'required' => false
                    ]
                ]
            ],
            'status' => 'Published',
            'created_by' => $user->id,
            'campus_code' => $user->campus_code,
        ]);

        $this->command->info('Test template created successfully!');
    }
}

