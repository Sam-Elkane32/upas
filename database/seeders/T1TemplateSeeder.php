<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;
use App\Models\User;

class T1TemplateSeeder extends Seeder
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

        // Create T1 template based on SG1 Table 1 structure from the PDF
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
                        'label' => 'Program Name',
                        'type' => 'text',
                        'required' => true
                    ],
                    [
                        'label' => 'Major Name',
                        'type' => 'text',
                        'required' => false
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
                        'label' => 'Supporting Document',
                        'type' => 'link',
                        'required' => true
                    ],
                    [
                        'label' => 'Evidence Verified',
                        'type' => 'dropdown',
                        'required' => true,
                        'options' => ['YES', 'NO']
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

        $this->command->info('T1 Template created successfully based on SG1 Table 1 structure!');
    }
}

