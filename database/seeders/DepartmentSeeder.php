<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds for PSU departments
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'College of Arts and Sciences',
                'code' => 'CAS',
                'description' => 'Liberal arts, natural sciences, and basic education'
            ],
            [
                'name' => 'College of Business',
                'code' => 'COB',
                'description' => 'Business administration and management programs'
            ],
            [
                'name' => 'College of Engineering and Architecture',
                'code' => 'CEA',
                'description' => 'Engineering and architectural programs'
            ],
            [
                'name' => 'College of Education',
                'code' => 'COE',
                'description' => 'Teacher education and educational management'
            ],
            [
                'name' => 'College of Agriculture and Aquatic Sciences',
                'code' => 'CAAS',
                'description' => 'Agricultural and fisheries programs'
            ],
            [
                'name' => 'College of Computer Studies',
                'code' => 'CCS',
                'description' => 'Information technology and computer science'
            ],
            [
                'name' => 'Graduate School',
                'code' => 'GS',
                'description' => 'Graduate degree programs and research'
            ],
            [
                'name' => 'University Research Office',
                'code' => 'URO',
                'description' => 'University research coordination and management'
            ],
            [
                'name' => 'Office of Student Affairs',
                'code' => 'OSA',
                'description' => 'Student services and activities'
            ],
            [
                'name' => 'Human Resource Management Office',
                'code' => 'HRMO',
                'description' => 'Personnel and human resource management'
            ]
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
