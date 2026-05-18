<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Submission;
use App\Models\User;

class ManualSubmissionSeeder extends Seeder
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

        // Create a manual submission with real data for T1 template
        $submission = Submission::create([
            'template_code' => 'T1',
            'form_title' => 'Office of the VPASS - Curriculum Development',
            'sg_code' => 'SG1',
            'kra_title' => 'KRA1.1 Relevant Curriculum',
            'kpi_title' => 'Number of reviewed, enhanced, and CHED-approved curriculum',
            'campus' => $user->campusInfo->name ?? $user->campus,
            'quarter' => '1st Q',
            'table_data' => [
                [
                    'responsible_work_units' => 'Alaminos City',
                    'quarter' => '1st Q',
                    'program_name' => 'BS Information Technology',
                    'major_name' => 'Software Development',
                    'target_output' => '1',
                    'actual_output' => '1',
                    'supporting_document' => 'https://drive.google.com/file/d/1abc123def456ghi789jkl/view',
                    'evidence_verified' => 'YES',
                    'remarks' => 'CHED-approved curriculum with updated syllabus'
                ],
                [
                    'responsible_work_units' => 'Alaminos City',
                    'quarter' => '1st Q',
                    'program_name' => 'BS Business Administration',
                    'major_name' => 'Marketing Management',
                    'target_output' => '1',
                    'actual_output' => '1',
                    'supporting_document' => 'https://drive.google.com/file/d/2def456ghi789jkl012mno/view',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Revised curriculum approved by CHED'
                ],
                [
                    'responsible_work_units' => 'Alaminos City',
                    'quarter' => '1st Q',
                    'program_name' => 'BS Education',
                    'major_name' => 'Elementary Education',
                    'target_output' => '1',
                    'actual_output' => '1',
                    'supporting_document' => 'https://drive.google.com/file/d/3ghi789jkl012mno345pqr/view',
                    'evidence_verified' => 'NO',
                    'remarks' => 'Pending CHED review and approval'
                ],
                [
                    'responsible_work_units' => 'CI/CED',
                    'quarter' => '1st Q',
                    'program_name' => 'BS Computer Science',
                    'major_name' => 'Data Science',
                    'target_output' => '1',
                    'actual_output' => '1',
                    'supporting_document' => 'https://drive.google.com/file/d/4jkl012mno345pqr678stu/view',
                    'evidence_verified' => 'YES',
                    'remarks' => 'New program approved by CHED'
                ],
                [
                    'responsible_work_units' => 'CI/CED',
                    'quarter' => '1st Q',
                    'program_name' => 'BS Psychology',
                    'major_name' => 'Clinical Psychology',
                    'target_output' => '1',
                    'actual_output' => '1',
                    'supporting_document' => 'https://drive.google.com/file/d/5mno345pqr678stu901vwx/view',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Enhanced curriculum with new courses'
                ]
            ],
            'status' => 'Pending Review',
            'submitted_by' => $user->id,
        ]);

        $this->command->info('Manual submission created successfully with ID: ' . $submission->id);
        $this->command->info('Submission contains 5 rows of data for T1 template');
        $this->command->info('Status: Pending Review - Ready for Campus Admin approval');
    }
}

