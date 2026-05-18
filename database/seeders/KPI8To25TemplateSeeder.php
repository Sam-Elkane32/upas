<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Str;

class KPI8To25TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates UAPS-compatible Templates + Fields + Campus User Submissions for KPIs 8-25
     * Based on T7-T12 formats
     */
    public function run(): void
    {
        // Get all campuses with admin and creator_editor users
        $campuses = \App\Models\Campus::where('is_active', true)->get();
        
        if ($campuses->isEmpty()) {
            $this->command->error('No active campuses found. Please create campuses first.');
            return;
        }

        $campusData = [];
        
        foreach ($campuses as $campus) {
            $admin = User::where('campus_code', $campus->code)->where('role', 'admin')->first();
            $user = User::where('campus_code', $campus->code)->where('role', 'creator_editor')->first();
            
            if ($admin && $user) {
                $campusData[] = [
                    'campus' => $campus,
                    'admin' => $admin,
                    'user' => $user,
                    'campusName' => $campus->name
                ];
            } else {
                $this->command->warn("Skipping campus {$campus->name} ({$campus->code}): Missing admin or creator_editor user.");
            }
        }

        if (empty($campusData)) {
            $this->command->error('No campuses found with both admin and creator_editor users. Please create users first.');
            return;
        }

        $this->command->info('Found ' . count($campusData) . ' campus(es) with required users.');

        $this->command->info('Generating Templates and Submissions for KPIs 8-25 (T7-T12 formats)...');

        // Generate for each campus
        foreach ($campusData as $data) {
            $this->command->info("Processing campus: {$data['campusName']} ({$data['campus']->code})");
            
            // KPI 8 - T7 Format: Design Thinking / Industry 4.0 / Technopreneurship / 21st Century Skills
            $this->createKPI8Template($data['admin'], $data['user'], $data['campus']);

            // KPI 9 - T8 Format: Graduate Promotion Percentage
            $this->createKPI9Template($data['admin'], $data['user'], $data['campus']);

            // KPI 10 - T9 Format: MS Recruitment, MS Graduates, MS Programs, PhD Programs
            $this->createKPI10Template($data['admin'], $data['user'], $data['campus']);

            // KPI 11 - T10 Format: STEAM Undergrad/Grad Count per Campus
            $this->createKPI11Template($data['admin'], $data['user'], $data['campus']);

            // KPI 12 - T11 Format: STEAM Undergrad Aligned with 21st Century, Industry 4.0, Marketing Integration
            $this->createKPI12Template($data['admin'], $data['user'], $data['campus']);

            // KPI 13 - T12 Format: Revised/Crafted QMS Processes
            $this->createKPI13Template($data['admin'], $data['user'], $data['campus']);

            // KPIs 14-25 (Additional templates following similar patterns)
            for ($kpi = 14; $kpi <= 25; $kpi++) {
                $this->createGenericKPITemplate($kpi, $data['admin'], $data['user'], $data['campus']);
            }
        }

        $this->command->info('✅ All Templates and Submissions for KPIs 8-25 created successfully!');
    }

    /**
     * Get Responsible Work Unit based on campus code
     */
    private function getResponsibleWorkUnit($campusCode)
    {
        $mapping = [
            'LINGAYEN' => 'CI/CED',
            'ALAMINOS' => 'CI/CED',
            'URDANETA' => 'CI/CED',
        ];
        
        return $mapping[$campusCode] ?? 'CI/CED';
    }

    /**
     * KPI 8 - T7 Format: Design Thinking / Industry 4.0 / Technopreneurship / 21st Century Skills
     */
    private function createKPI8Template($admin, $user, $campus)
    {
        $templateCode = 'T7';
        $kraTitle = 'KRA1.2 INNOVATION AND INDUSTRY INTEGRATION';
        $kpiTitle = '8 - Number of programs with Design Thinking / Industry 4.0 / Technopreneurship / 21st Century Skills integration';
        $formTitle = 'SG1 Table 7';

        $fieldsJson = [
            'fields' => [
                [
                    'key' => 'responsible_work_units',
                    'label' => 'Responsible Work Units',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'quarter',
                    'label' => 'Quarter',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q']
                ],
                [
                    'key' => 'program_name',
                    'label' => 'Program Name',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'major_name',
                    'label' => 'Major Name',
                    'type' => 'text',
                    'required' => false
                ],
                [
                    'key' => 'integration_type',
                    'label' => 'Integration Type',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['Design Thinking', 'Industry 4.0', 'Technopreneurship', '21st Century Skills', 'Multiple']
                ],
                [
                    'key' => 'target_output',
                    'label' => 'Target Output',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'actual_output',
                    'label' => 'Actual Output',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'google_drive_link',
                    'label' => 'Google Drive Link',
                    'type' => 'link',
                    'required' => true
                ],
                [
                    'key' => 'evidence_verified',
                    'label' => 'Evidence Verified',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['YES', 'NO']
                ],
                [
                    'key' => 'ci_office_comments',
                    'label' => 'CI Office Comments',
                    'type' => 'textarea',
                    'required' => false
                ]
            ]
        ];

        // Create template for this campus
        $this->createTemplateForCampus($templateCode, 'SG1', $kraTitle, $kpiTitle, $fieldsJson, $admin, $campus);

        // Create submission
        $this->createKPI8Submission($templateCode, $formTitle, $campus, $user);
    }

    /**
     * KPI 9 - T8 Format: Graduate Promotion Percentage
     */
    private function createKPI9Template($admin, $user, $campus)
    {
        $templateCode = 'T8';
        $kraTitle = 'KRA1.3 STUDENT DEVELOPMENT';
        $kpiTitle = '9 - Percentage of graduates promoted within 6 months after graduation';
        $formTitle = 'SG1 Table 8';

        $fieldsJson = [
            'fields' => [
                [
                    'key' => 'responsible_work_units',
                    'label' => 'Responsible Work Units',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'quarter',
                    'label' => 'Quarter',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q']
                ],
                [
                    'key' => 'program_name',
                    'label' => 'Program Name',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'graduation_year',
                    'label' => 'Graduation Year',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'total_graduates',
                    'label' => 'Total Graduates',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'promoted_within_6months',
                    'label' => 'Promoted Within 6 Months',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'promotion_percentage',
                    'label' => 'Promotion Percentage (%)',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'google_drive_link',
                    'label' => 'Google Drive Link',
                    'type' => 'link',
                    'required' => true
                ],
                [
                    'key' => 'evidence_verified',
                    'label' => 'Evidence Verified',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['YES', 'NO']
                ],
                [
                    'key' => 'remarks',
                    'label' => 'Remarks',
                    'type' => 'textarea',
                    'required' => false
                ]
            ]
        ];

        $this->createTemplateForCampus($templateCode, 'SG1', $kraTitle, $kpiTitle, $fieldsJson, $admin, $campus);
        $this->createKPI9Submission($templateCode, $formTitle, $campus, $user);
    }

    /**
     * KPI 10 - T9 Format: MS Recruitment, MS Graduates, MS Programs, PhD Programs
     */
    private function createKPI10Template($admin, $user, $campus)
    {
        $templateCode = 'T9';
        $kraTitle = 'KRA1.4 GRADUATE EDUCATION';
        $kpiTitle = '10 - Number of MS Recruitment, MS Graduates, MS Programs, PhD Programs';
        $formTitle = 'SG1 Table 9';

        $fieldsJson = [
            'fields' => [
                [
                    'key' => 'responsible_work_units',
                    'label' => 'Responsible Work Units',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'quarter',
                    'label' => 'Quarter',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q']
                ],
                [
                    'key' => 'program_type',
                    'label' => 'Program Type',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['MS Recruitment', 'MS Graduates', 'MS Programs', 'PhD Programs']
                ],
                [
                    'key' => 'program_name',
                    'label' => 'Program Name',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'target_output',
                    'label' => 'Target Output',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'actual_output',
                    'label' => 'Actual Output',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'google_drive_link',
                    'label' => 'Google Drive Link',
                    'type' => 'link',
                    'required' => true
                ],
                [
                    'key' => 'evidence_verified',
                    'label' => 'Evidence Verified',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['YES', 'NO']
                ],
                [
                    'key' => 'remarks',
                    'label' => 'Remarks',
                    'type' => 'textarea',
                    'required' => false
                ]
            ]
        ];

        $this->createTemplateForCampus($templateCode, 'SG1', $kraTitle, $kpiTitle, $fieldsJson, $admin, $campus);
        $this->createKPI10Submission($templateCode, $formTitle, $campus, $user);
    }

    /**
     * KPI 11 - T10 Format: STEAM Undergrad/Grad Count per Campus
     */
    private function createKPI11Template($admin, $user, $campus)
    {
        $templateCode = 'T10';
        $kraTitle = 'KRA1.5 STEAM PROGRAMS';
        $kpiTitle = '11 - Number of STEAM undergraduate and graduate programs per campus';
        $formTitle = 'SG1 Table 10';

        $fieldsJson = [
            'fields' => [
                [
                    'key' => 'responsible_work_units',
                    'label' => 'Responsible Work Units',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'quarter',
                    'label' => 'Quarter',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q']
                ],
                [
                    'key' => 'program_level',
                    'label' => 'Program Level',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['Undergraduate', 'Graduate']
                ],
                [
                    'key' => 'steam_category',
                    'label' => 'STEAM Category',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['Science', 'Technology', 'Engineering', 'Arts', 'Mathematics']
                ],
                [
                    'key' => 'program_name',
                    'label' => 'Program Name',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'campus',
                    'label' => 'Campus',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'student_count',
                    'label' => 'Student Count',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'google_drive_link',
                    'label' => 'Google Drive Link',
                    'type' => 'link',
                    'required' => true
                ],
                [
                    'key' => 'evidence_verified',
                    'label' => 'Evidence Verified',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['YES', 'NO']
                ],
                [
                    'key' => 'remarks',
                    'label' => 'Remarks',
                    'type' => 'textarea',
                    'required' => false
                ]
            ]
        ];

        $this->createTemplateForCampus($templateCode, 'SG1', $kraTitle, $kpiTitle, $fieldsJson, $admin, $campus);
        $this->createKPI11Submission($templateCode, $formTitle, $campus, $user);
    }

    /**
     * KPI 12 - T11 Format: STEAM Undergrad Aligned with 21st Century, Industry 4.0, Marketing Integration
     */
    private function createKPI12Template($admin, $user, $campus)
    {
        $templateCode = 'T11';
        $kraTitle = 'KRA1.5 STEAM PROGRAMS';
        $kpiTitle = '12 - Number of STEAM undergraduate programs aligned with 21st century skills, Industry 4.0, and marketing integration';
        $formTitle = 'SG1 Table 11';

        $fieldsJson = [
            'fields' => [
                [
                    'key' => 'responsible_work_units',
                    'label' => 'Responsible Work Units',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'quarter',
                    'label' => 'Quarter',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q']
                ],
                [
                    'key' => 'program_name',
                    'label' => 'Program Name',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'alignment_type',
                    'label' => 'Alignment Type',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['21st Century Skills', 'Industry 4.0', 'Marketing Integration', 'All Three']
                ],
                [
                    'key' => 'target_output',
                    'label' => 'Target Output',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'actual_output',
                    'label' => 'Actual Output',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'google_drive_link',
                    'label' => 'Google Drive Link',
                    'type' => 'link',
                    'required' => true
                ],
                [
                    'key' => 'evidence_verified',
                    'label' => 'Evidence Verified',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['YES', 'NO']
                ],
                [
                    'key' => 'ci_office_comments',
                    'label' => 'CI Office Comments',
                    'type' => 'textarea',
                    'required' => false
                ]
            ]
        ];

        $this->createTemplateForCampus($templateCode, 'SG1', $kraTitle, $kpiTitle, $fieldsJson, $admin, $campus);
        $this->createKPI12Submission($templateCode, $formTitle, $campus, $user);
    }

    /**
     * KPI 13 - T12 Format: Revised/Crafted QMS Processes
     */
    private function createKPI13Template($admin, $user, $campus)
    {
        $templateCode = 'T12';
        $kraTitle = 'KRA1.6 QUALITY MANAGEMENT';
        $kpiTitle = '13 - Number of revised/crafted QMS processes';
        $formTitle = 'SG1 Table 12';

        $fieldsJson = [
            'fields' => [
                [
                    'key' => 'responsible_work_units',
                    'label' => 'Responsible Work Units',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'quarter',
                    'label' => 'Quarter',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q']
                ],
                [
                    'key' => 'process_type',
                    'label' => 'Process Type',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['Revised', 'Crafted', 'Both']
                ],
                [
                    'key' => 'process_name',
                    'label' => 'Process Name',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'revision_reason',
                    'label' => 'Reason for Revision',
                    'type' => 'textarea',
                    'required' => false
                ],
                [
                    'key' => 'target_output',
                    'label' => 'Target Output',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'actual_output',
                    'label' => 'Actual Output',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'google_drive_link',
                    'label' => 'Google Drive Link',
                    'type' => 'link',
                    'required' => true
                ],
                [
                    'key' => 'evidence_verified',
                    'label' => 'Evidence Verified',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['YES', 'NO']
                ],
                [
                    'key' => 'remarks',
                    'label' => 'Remarks',
                    'type' => 'textarea',
                    'required' => false
                ]
            ]
        ];

        $this->createTemplateForCampus($templateCode, 'SG1', $kraTitle, $kpiTitle, $fieldsJson, $admin, $campus);
        $this->createKPI13Submission($templateCode, $formTitle, $campus, $user);
    }

    /**
     * Generic KPI Template for KPIs 14-25
     */
    private function createGenericKPITemplate($kpiNumber, $admin, $user, $campus)
    {
        // Map KPI numbers to template codes T13-T24
        $templateCode = 'T' . ($kpiNumber + 5); // KPI 14 = T19, KPI 15 = T20, etc.
        $kraNumber = ceil(($kpiNumber - 7) / 3) + 1; // Distribute across KRAs
        $kraTitle = "KRA1.{$kraNumber} GENERAL KRA";
        $kpiTitle = "{$kpiNumber} - General Accomplishment Metric";
        $formTitle = "SG1 Table " . ($kpiNumber + 5);

        $fieldsJson = [
            'fields' => [
                [
                    'key' => 'responsible_work_units',
                    'label' => 'Responsible Work Units',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'quarter',
                    'label' => 'Quarter',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q']
                ],
                [
                    'key' => 'program_name',
                    'label' => 'Program Name',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'key' => 'major_name',
                    'label' => 'Major Name',
                    'type' => 'text',
                    'required' => false
                ],
                [
                    'key' => 'target_output',
                    'label' => 'Target Output',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'actual_output',
                    'label' => 'Actual Output',
                    'type' => 'number',
                    'required' => true
                ],
                [
                    'key' => 'google_drive_link',
                    'label' => 'Google Drive Link',
                    'type' => 'link',
                    'required' => true
                ],
                [
                    'key' => 'evidence_verified',
                    'label' => 'Evidence Verified',
                    'type' => 'dropdown',
                    'required' => true,
                    'options' => ['YES', 'NO']
                ],
                [
                    'key' => 'remarks',
                    'label' => 'Remarks',
                    'type' => 'textarea',
                    'required' => false
                ]
            ]
        ];

        $this->createTemplateForCampus($templateCode, 'SG1', $kraTitle, $kpiTitle, $fieldsJson, $admin, $campus);
        $this->createGenericSubmission($templateCode, $formTitle, $campus, $user, $kpiNumber);
    }

    /**
     * Create template for a campus
     */
    private function createTemplateForCampus($templateCode, $sgCode, $kraTitle, $kpiTitle, $fieldsJson, $admin, $campus)
    {
        // Check if template already exists for this campus
        $existing = Template::where('template_code', $templateCode)
            ->where('campus_code', $campus->code)
            ->first();

        if ($existing) {
            $this->command->warn("  ⚠ Template {$templateCode} already exists for {$campus->name}, skipping...");
            return;
        }

        Template::create([
            'sg_code' => $sgCode,
            'template_code' => $templateCode,
            'kra_title' => $kraTitle,
            'kpi_title' => $kpiTitle,
            'fields_json' => $fieldsJson,
            'status' => 'Published',
            'created_by' => $admin->id,
            'campus_code' => $campus->code,
        ]);

        $this->command->info("  ✅ Template Created: {$templateCode} ({$campus->name})");
    }

    /**
     * Generate UAPS-style submission ID (UAPS-{hex}-{date})
     */
    private function generateSubmissionId(): string
    {
        // Generate random hex string (13 characters like existing format)
        $hex = strtoupper(bin2hex(random_bytes(7)));
        $date = now()->format('Ymd');
        return 'UAPS-' . $hex . '-' . $date;
    }

    /**
     * KPI 8 Submission Data
     */
    private function createKPI8Submission($templateCode, $formTitle, $campus, $user)
    {
        $campusName = $campus->name;
        $responsibleWorkUnit = $this->getResponsibleWorkUnit($campus->code);
        
        Submission::create([
            'submission_id' => $this->generateSubmissionId(),
            'template_code' => $templateCode,
            'form_title' => $formTitle,
            'sg_code' => 'SG1',
            'kra_title' => 'KRA1.2 INNOVATION AND INDUSTRY INTEGRATION',
            'kpi_title' => '8 - Number of programs with Design Thinking / Industry 4.0 / Technopreneurship / 21st Century Skills integration',
            'campus' => $campusName,
            'quarter' => '1st Q',
            'table_data' => [
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Information Technology',
                    'major_name' => 'Software Engineering',
                    'integration_type' => 'Industry 4.0',
                    'target_output' => '3',
                    'actual_output' => '3',
                    'google_drive_link' => 'https://drive.google.com/file/d/1abc123def456ghi789jkl/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'ci_office_comments' => 'Successfully integrated Industry 4.0 concepts into curriculum'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Business Administration',
                    'major_name' => 'Marketing Management',
                    'integration_type' => 'Design Thinking',
                    'target_output' => '2',
                    'actual_output' => '2',
                    'google_drive_link' => 'https://drive.google.com/file/d/2def456ghi789jkl012mno/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'ci_office_comments' => 'Design thinking methodology incorporated in marketing courses'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Computer Science',
                    'major_name' => 'Data Science',
                    'integration_type' => 'Technopreneurship',
                    'target_output' => '2',
                    'actual_output' => '2',
                    'google_drive_link' => 'https://drive.google.com/file/d/3ghi789jkl012mno345pqr/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'ci_office_comments' => 'Technopreneurship track added to data science program'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Education',
                    'major_name' => 'Elementary Education',
                    'integration_type' => '21st Century Skills',
                    'target_output' => '4',
                    'actual_output' => '4',
                    'google_drive_link' => 'https://drive.google.com/file/d/4jkl012mno345pqr678stu/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'ci_office_comments' => '21st century skills framework integrated across all education courses'
                ]
            ],
            'status' => 'Pending Review',
            'submitted_by' => $user->id,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->command->info("  ✓ Submission Created: {$formTitle} ({$campusName})");
    }

    /**
     * KPI 9 Submission Data
     */
    private function createKPI9Submission($templateCode, $formTitle, $campus, $user)
    {
        $campusName = $campus->name;
        $responsibleWorkUnit = $this->getResponsibleWorkUnit($campus->code);
        
        Submission::create([
            'submission_id' => $this->generateSubmissionId(),
            'template_code' => $templateCode,
            'form_title' => $formTitle,
            'sg_code' => 'SG1',
            'kra_title' => 'KRA1.3 STUDENT DEVELOPMENT',
            'kpi_title' => '9 - Percentage of graduates promoted within 6 months after graduation',
            'campus' => $campusName,
            'quarter' => '1st Q',
            'table_data' => [
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Information Technology',
                    'graduation_year' => '2024',
                    'total_graduates' => '45',
                    'promoted_within_6months' => '38',
                    'promotion_percentage' => '84.44',
                    'google_drive_link' => 'https://drive.google.com/file/d/5mno345pqr678stu901vwx/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'High promotion rate due to industry partnerships'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Business Administration',
                    'graduation_year' => '2024',
                    'total_graduates' => '52',
                    'promoted_within_6months' => '42',
                    'promotion_percentage' => '80.77',
                    'google_drive_link' => 'https://drive.google.com/file/d/6pqr678stu901vwx234yza/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Strong job placement support from career services'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Education',
                    'graduation_year' => '2024',
                    'total_graduates' => '38',
                    'promoted_within_6months' => '35',
                    'promotion_percentage' => '92.11',
                    'google_drive_link' => 'https://drive.google.com/file/d/7stu901vwx234yza567bcd/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Excellent promotion rate in education sector'
                ]
            ],
            'status' => 'Pending Review',
            'submitted_by' => $user->id,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->command->info("  ✓ Submission Created: {$formTitle} ({$campusName})");
    }

    /**
     * KPI 10 Submission Data
     */
    private function createKPI10Submission($templateCode, $formTitle, $campus, $user)
    {
        $campusName = $campus->name;
        $responsibleWorkUnit = $this->getResponsibleWorkUnit($campus->code);
        
        Submission::create([
            'submission_id' => $this->generateSubmissionId(),
            'template_code' => $templateCode,
            'form_title' => $formTitle,
            'sg_code' => 'SG1',
            'kra_title' => 'KRA1.4 GRADUATE EDUCATION',
            'kpi_title' => '10 - Number of MS Recruitment, MS Graduates, MS Programs, PhD Programs',
            'campus' => $campusName,
            'quarter' => '1st Q',
            'table_data' => [
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_type' => 'MS Recruitment',
                    'program_name' => 'MS Information Technology',
                    'target_output' => '25',
                    'actual_output' => '28',
                    'google_drive_link' => 'https://drive.google.com/file/d/8vwx234yza567bcd890efg/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Exceeded recruitment target for MS IT program'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_type' => 'MS Graduates',
                    'program_name' => 'MS Business Administration',
                    'target_output' => '15',
                    'actual_output' => '15',
                    'google_drive_link' => 'https://drive.google.com/file/d/9yza567bcd890efg123hij/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Met target for MS BA graduates'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_type' => 'MS Programs',
                    'program_name' => 'MS Education',
                    'target_output' => '2',
                    'actual_output' => '2',
                    'google_drive_link' => 'https://drive.google.com/file/d/10bcd890efg123hij456klm/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Two MS Education programs active'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_type' => 'PhD Programs',
                    'program_name' => 'PhD Information Technology',
                    'target_output' => '1',
                    'actual_output' => '1',
                    'google_drive_link' => 'https://drive.google.com/file/d/11efg123hij456klm789nop/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'PhD IT program successfully launched'
                ]
            ],
            'status' => 'Pending Review',
            'submitted_by' => $user->id,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->command->info("  ✓ Submission Created: {$formTitle} ({$campusName})");
    }

    /**
     * KPI 11 Submission Data
     */
    private function createKPI11Submission($templateCode, $formTitle, $campus, $user)
    {
        $campusName = $campus->name;
        $responsibleWorkUnit = $this->getResponsibleWorkUnit($campus->code);
        
        Submission::create([
            'submission_id' => $this->generateSubmissionId(),
            'template_code' => $templateCode,
            'form_title' => $formTitle,
            'sg_code' => 'SG1',
            'kra_title' => 'KRA1.5 STEAM PROGRAMS',
            'kpi_title' => '11 - Number of STEAM undergraduate and graduate programs per campus',
            'campus' => $campusName,
            'quarter' => '1st Q',
            'table_data' => [
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_level' => 'Undergraduate',
                    'steam_category' => 'Technology',
                    'program_name' => 'BS Information Technology',
                    'campus' => $campusName,
                    'student_count' => '245',
                    'google_drive_link' => 'https://drive.google.com/file/d/12hij456klm789nop012qrs/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Strong enrollment in IT program'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_level' => 'Undergraduate',
                    'steam_category' => 'Engineering',
                    'program_name' => 'BS Computer Engineering',
                    'campus' => $campusName,
                    'student_count' => '128',
                    'google_drive_link' => 'https://drive.google.com/file/d/13klm789nop012qrs345tuv/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Growing enrollment in engineering'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_level' => 'Graduate',
                    'steam_category' => 'Science',
                    'program_name' => 'MS Data Science',
                    'campus' => $campusName,
                    'student_count' => '42',
                    'google_drive_link' => 'https://drive.google.com/file/d/14nop012qrs345tuv678wxy/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Graduate program showing steady growth'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_level' => 'Undergraduate',
                    'steam_category' => 'Mathematics',
                    'program_name' => 'BS Mathematics',
                    'campus' => $campusName,
                    'student_count' => '89',
                    'google_drive_link' => 'https://drive.google.com/file/d/15qrs345tuv678wxy901zab/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Mathematics program maintaining enrollment'
                ]
            ],
            'status' => 'Pending Review',
            'submitted_by' => $user->id,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->command->info("  ✓ Submission Created: {$formTitle} ({$campusName})");
    }

    /**
     * KPI 12 Submission Data
     */
    private function createKPI12Submission($templateCode, $formTitle, $campus, $user)
    {
        $campusName = $campus->name;
        $responsibleWorkUnit = $this->getResponsibleWorkUnit($campus->code);
        
        Submission::create([
            'submission_id' => $this->generateSubmissionId(),
            'template_code' => $templateCode,
            'form_title' => $formTitle,
            'sg_code' => 'SG1',
            'kra_title' => 'KRA1.5 STEAM PROGRAMS',
            'kpi_title' => '12 - Number of STEAM undergraduate programs aligned with 21st century skills, Industry 4.0, and marketing integration',
            'campus' => $campusName,
            'quarter' => '1st Q',
            'table_data' => [
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Information Technology',
                    'alignment_type' => 'All Three',
                    'target_output' => '3',
                    'actual_output' => '3',
                    'google_drive_link' => 'https://drive.google.com/file/d/16tuv678wxy901zab234cde/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'ci_office_comments' => 'IT program fully aligned with all three integration types'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Business Administration',
                    'alignment_type' => 'Marketing Integration',
                    'target_output' => '2',
                    'actual_output' => '2',
                    'google_drive_link' => 'https://drive.google.com/file/d/17wxy901zab234cde567fgh/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'ci_office_comments' => 'Marketing integration successfully implemented'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Computer Science',
                    'alignment_type' => 'Industry 4.0',
                    'target_output' => '2',
                    'actual_output' => '2',
                    'google_drive_link' => 'https://drive.google.com/file/d/18zab234cde567fgh890ijk/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'ci_office_comments' => 'Industry 4.0 concepts integrated into CS curriculum'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'program_name' => 'BS Education',
                    'alignment_type' => '21st Century Skills',
                    'target_output' => '4',
                    'actual_output' => '4',
                    'google_drive_link' => 'https://drive.google.com/file/d/19cde567fgh890ijk123lmn/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'ci_office_comments' => '21st century skills framework embedded in education program'
                ]
            ],
            'status' => 'Pending Review',
            'submitted_by' => $user->id,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->command->info("  ✓ Submission Created: {$formTitle} ({$campusName})");
    }

    /**
     * KPI 13 Submission Data
     */
    private function createKPI13Submission($templateCode, $formTitle, $campus, $user)
    {
        $campusName = $campus->name;
        $responsibleWorkUnit = $this->getResponsibleWorkUnit($campus->code);
        
        Submission::create([
            'submission_id' => $this->generateSubmissionId(),
            'template_code' => $templateCode,
            'form_title' => $formTitle,
            'sg_code' => 'SG1',
            'kra_title' => 'KRA1.6 QUALITY MANAGEMENT',
            'kpi_title' => '13 - Number of revised/crafted QMS processes',
            'campus' => $campusName,
            'quarter' => '1st Q',
            'table_data' => [
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'process_type' => 'Revised',
                    'process_name' => 'Student Admission Process',
                    'revision_reason' => 'Updated to align with new CHED guidelines and digital transformation requirements',
                    'target_output' => '2',
                    'actual_output' => '2',
                    'google_drive_link' => 'https://drive.google.com/file/d/20fgh890ijk123lmn456opq/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Process revision completed and documented'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'process_type' => 'Crafted',
                    'process_name' => 'Online Learning Assessment Process',
                    'revision_reason' => 'New process created to support hybrid learning model',
                    'target_output' => '1',
                    'actual_output' => '1',
                    'google_drive_link' => 'https://drive.google.com/file/d/21ijk123lmn456opq789rst/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'New QMS process successfully crafted and implemented'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'process_type' => 'Revised',
                    'process_name' => 'Faculty Performance Evaluation Process',
                    'revision_reason' => 'Enhanced to include online teaching competencies and student feedback mechanisms',
                    'target_output' => '1',
                    'actual_output' => '1',
                    'google_drive_link' => 'https://drive.google.com/file/d/22lmn456opq789rst012uvw/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Faculty evaluation process updated with new metrics'
                ],
                [
                    'responsible_work_units' => $responsibleWorkUnit,
                    'quarter' => '1st Q',
                    'process_type' => 'Both',
                    'process_name' => 'Research Publication Process',
                    'revision_reason' => 'Comprehensive revision and new sub-processes added for open access publishing',
                    'target_output' => '3',
                    'actual_output' => '3',
                    'google_drive_link' => 'https://drive.google.com/file/d/23opq789rst012uvw345xyz/view?usp=sharing',
                    'evidence_verified' => 'YES',
                    'remarks' => 'Research process fully revised with new components'
                ]
            ],
            'status' => 'Pending Review',
            'submitted_by' => $user->id,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->command->info("  ✓ Submission Created: {$formTitle} ({$campusName})");
    }

    /**
     * Generic Submission for KPIs 14-25
     */
    private function createGenericSubmission($templateCode, $formTitle, $campus, $user, $kpiNumber)
    {
        $campusName = $campus->name;
        $responsibleWorkUnit = $this->getResponsibleWorkUnit($campus->code);
        
        $programs = [
            'BS Information Technology',
            'BS Business Administration',
            'BS Education',
            'BS Computer Science',
            'BS Mathematics'
        ];

        $majors = [
            'Software Engineering',
            'Marketing Management',
            'Elementary Education',
            'Data Science',
            'Applied Mathematics'
        ];

        $tableData = [];
        for ($i = 0; $i < 3; $i++) {
            $tableData[] = [
                'responsible_work_units' => $responsibleWorkUnit,
                'quarter' => '1st Q',
                'program_name' => $programs[$i % count($programs)],
                'major_name' => $majors[$i % count($majors)],
                'target_output' => (string)(rand(1, 5)),
                'actual_output' => (string)(rand(1, 5)),
                'google_drive_link' => 'https://drive.google.com/file/d/' . Str::random(20) . '/view?usp=sharing',
                'evidence_verified' => rand(0, 1) ? 'YES' : 'NO',
                'remarks' => "Sample data for KPI {$kpiNumber} - Row " . ($i + 1)
            ];
        }

        $kraNumber = ceil(($kpiNumber - 7) / 3) + 1;
        
        Submission::create([
            'submission_id' => $this->generateSubmissionId(),
            'template_code' => $templateCode,
            'form_title' => $formTitle,
            'sg_code' => 'SG1',
            'kra_title' => "KRA1.{$kraNumber} GENERAL KRA",
            'kpi_title' => "{$kpiNumber} - General Accomplishment Metric",
            'campus' => $campusName,
            'quarter' => '1st Q',
            'table_data' => $tableData,
            'status' => 'Pending Review',
            'submitted_by' => $user->id,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->command->info("  ✓ Submission Created: {$formTitle} ({$campusName})");
    }
}

