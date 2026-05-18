<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StrategicGoal;
use App\Models\KeyPerformanceIndicator;
use App\Models\Department;

class StrategicGoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get departments
        $departments = Department::all();
        
        if ($departments->isEmpty()) {
            $this->command->warn('No departments found. Please run department seeder first.');
            return;
        }

        // PSU Strategic Goals for 2025
        $strategicGoalsData = [
            [
                'title' => 'Enhance Academic Excellence and Innovation',
                'description' => 'Improve the quality of education through innovative teaching methods, curriculum development, and faculty enhancement programs.',
                'category' => 'Academic Excellence',
                'target_year' => 2025,
                'weight_percentage' => 30.00,
                'success_indicators' => 'Increased program accreditation, improved student performance, enhanced faculty qualifications',
                'kpis' => [
                    [
                        'name' => 'Program Accreditation Rate',
                        'description' => 'Percentage of programs with CHED/professional accreditation',
                        'measurement_type' => 'Percentage',
                        'target_value' => 85.00,
                        'current_value' => 65.00,
                        'unit_of_measure' => '%',
                        'frequency' => 'Annually',
                        'deadline' => '2025-12-31',
                        'quarterly_targets' => ['Q1' => 70, 'Q2' => 75, 'Q3' => 80, 'Q4' => 85]
                    ],
                    [
                        'name' => 'Faculty with Graduate Degrees',
                        'description' => 'Percentage of faculty members with Masters/PhD degrees',
                        'measurement_type' => 'Percentage',
                        'target_value' => 90.00,
                        'current_value' => 75.00,
                        'unit_of_measure' => '%',
                        'frequency' => 'Quarterly',
                        'deadline' => '2025-12-31',
                        'quarterly_targets' => ['Q1' => 80, 'Q2' => 85, 'Q3' => 88, 'Q4' => 90]
                    ]
                ]
            ],
            [
                'title' => 'Strengthen Research and Development Capabilities',
                'description' => 'Enhance research output, innovation, and technology transfer to address regional and national development needs.',
                'category' => 'Research & Innovation',
                'target_year' => 2025,
                'weight_percentage' => 25.00,
                'success_indicators' => 'Increased research publications, patents filed, research grants secured',
                'kpis' => [
                    [
                        'name' => 'Research Publications',
                        'description' => 'Number of peer-reviewed publications by PSU faculty',
                        'measurement_type' => 'Number',
                        'target_value' => 150.00,
                        'current_value' => 45.00,
                        'unit_of_measure' => 'publications',
                        'frequency' => 'Quarterly',
                        'deadline' => '2025-12-31',
                        'quarterly_targets' => ['Q1' => 40, 'Q2' => 80, 'Q3' => 120, 'Q4' => 150]
                    ],
                    [
                        'name' => 'Research Grant Amount',
                        'description' => 'Total value of research grants secured',
                        'measurement_type' => 'Currency',
                        'target_value' => 5000000.00,
                        'current_value' => 1200000.00,
                        'unit_of_measure' => 'PHP',
                        'frequency' => 'Quarterly',
                        'deadline' => '2025-12-31',
                        'quarterly_targets' => ['Q1' => 1500000, 'Q2' => 2500000, 'Q3' => 3500000, 'Q4' => 5000000]
                    ]
                ]
            ],
            [
                'title' => 'Expand Community Extension and Outreach Programs',
                'description' => 'Strengthen university-community partnerships through relevant extension programs and social responsibility initiatives.',
                'category' => 'Community Extension',
                'target_year' => 2025,
                'weight_percentage' => 20.00,
                'success_indicators' => 'Increased community partnerships, extension program participation, social impact assessment',
                'kpis' => [
                    [
                        'name' => 'Community Partnerships',
                        'description' => 'Number of active community partnerships and MOUs',
                        'measurement_type' => 'Number',
                        'target_value' => 50.00,
                        'current_value' => 28.00,
                        'unit_of_measure' => 'partnerships',
                        'frequency' => 'Quarterly',
                        'deadline' => '2025-12-31',
                        'quarterly_targets' => ['Q1' => 35, 'Q2' => 40, 'Q3' => 45, 'Q4' => 50]
                    ]
                ]
            ]
        ];

        foreach ($strategicGoalsData as $goalData) {
            // Randomly assign to departments (in real scenario, this would be strategic)
            $department = $departments->random();
            
            // Create strategic goal
            $kpisData = $goalData['kpis'];
            unset($goalData['kpis']);
            $goalData['department_id'] = $department->id;
            
            $strategicGoal = StrategicGoal::create($goalData);
            
            // Create KPIs for this goal
            foreach ($kpisData as $kpiData) {
                $kpiData['strategic_goal_id'] = $strategicGoal->id;
                KeyPerformanceIndicator::create($kpiData);
            }
        }

        $this->command->info('Strategic Goals and KPIs seeded successfully!');
    }
}
