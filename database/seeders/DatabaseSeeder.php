<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Campus;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Clear existing data (child tables before parents due to FK constraints)
        \Illuminate\Support\Facades\DB::table('campus_admins')->delete();
        User::query()->delete();

        // Create campuses first
        $this->createCampuses();
        
        // Create departments
        $this->createDepartments();
        
        // Create all user accounts
        $this->createUsers();

        // Beta / developer (Messages-only) accounts — safe defaults; override via .env in DeveloperAccountsSeeder
        $this->call(DeveloperAccountsSeeder::class);
        
        // Seed templates (only if templates table exists)
        if (\Illuminate\Support\Facades\Schema::hasTable('templates')) {
            $this->call(TemplateSeeder::class);
        }

    }
    
    private function createCampuses()
    {
        $campuses = [
            [
                'name' => 'PSU Lingayen Campus',
                'code' => 'LINGAYEN',
                'location' => 'Lingayen, Pangasinan',
                'description' => 'Main Campus',
                'is_active' => true,
            ],
            [
                'name' => 'PSU Alaminos Campus',
                'code' => 'ALAMINOS',
                'location' => 'Alaminos City, Pangasinan',
                'description' => 'Alaminos Campus',
                'is_active' => true,
            ],
            [
                'name' => 'PSU Urdaneta Campus',
                'code' => 'URDANETA',
                'location' => 'Urdaneta City, Pangasinan',
                'description' => 'Urdaneta Campus',
                'is_active' => true,
            ],
            [
                'name' => 'PSU Bayambang Campus',
                'code' => 'BAYAMBANG',
                'location' => 'Bayambang, Pangasinan',
                'description' => 'Bayambang Campus',
                'is_active' => true,
            ],
            [
                'name' => 'PSU Infanta Campus',
                'code' => 'INFANTA',
                'location' => 'Infanta, Pangasinan',
                'description' => 'Infanta Campus',
                'is_active' => true,
            ],
            [
                'name' => 'PSU San Carlos Campus',
                'code' => 'SANCARLOS',
                'location' => 'San Carlos City, Pangasinan',
                'description' => 'San Carlos Campus',
                'is_active' => true,
            ],
            [
                'name' => 'PSU Asingan Campus',
                'code' => 'ASINGAN',
                'location' => 'Asingan, Pangasinan',
                'description' => 'Asingan Campus',
                'is_active' => true,
            ],
            [
                'name' => 'PSU Binmaley Campus',
                'code' => 'BINMALEY',
                'location' => 'Binmaley, Pangasinan',
                'description' => 'Binmaley Campus',
                'is_active' => true,
            ],
            [
                'name' => 'PSU Sta. Maria Campus',
                'code' => 'STAMARIA',
                'location' => 'Sta. Maria, Pangasinan',
                'description' => 'Sta. Maria Campus',
                'is_active' => true,
            ],
        ];
        
        foreach ($campuses as $campus) {
            Campus::updateOrCreate(
                ['code' => $campus['code']],
                $campus
            );
        }
    }
    
    private function createDepartments()
    {
        $departments = [
            ['name' => 'Office of the President', 'code' => 'OP'],
            ['name' => 'Office of the Vice President for Academic Affairs', 'code' => 'OVPAA'],
            ['name' => 'Office of the Vice President for Administration', 'code' => 'OVPA'],
            ['name' => 'Office of the Vice President for Research, Extension and Development', 'code' => 'OVPRED'],
            ['name' => 'Office of the Vice President for Student Affairs', 'code' => 'OVPSA'],
            ['name' => 'Human Resource Management Office', 'code' => 'HRMO'],
            ['name' => 'Finance Office', 'code' => 'FO'],
            ['name' => 'Planning and Development Office', 'code' => 'PDO'],
            ['name' => 'Information Technology Office', 'code' => 'ITO'],
            ['name' => 'Registrar\'s Office', 'code' => 'RO'],
            ['name' => 'Library', 'code' => 'LIB'],
        ];
        
        foreach ($departments as $dept) {
            Department::updateOrCreate(['code' => $dept['code']], $dept);
        }
    }
    
    private function createUsers()
    {
        // Super Administrator
        User::create([
            'name' => 'UAPS Super Administrator',
            'email' => 'superadmin@psu.edu.ph',
            'password' => Hash::make('UAPS@2025!'),
            'employee_id' => 'SUPER001',
            'department' => 1, // Office of the President
            'position' => 'Super Administrator',
            'role' => 'super_admin',
            'campus' => 'All Campuses',
            'campus_code' => 'ALL',
            'phone_number' => '+63-917-123-4567',
            'is_active' => true,
            'is_approved' => true,
            'approved_at' => now(),
        ]);
        
        // Campus Administrators
        $campusAdmins = [
            [
                'name' => 'Dr. Maria Santos',
                'email' => 'admin.lingayen@psu.edu.ph',
                'campus' => 'PSU Lingayen Campus',
                'campus_code' => 'LINGAYEN',
                'employee_id' => 'ADMIN001',
            ],
            [
                'name' => 'Dr. Juan Dela Cruz',
                'email' => 'admin.alaminos@psu.edu.ph',
                'campus' => 'PSU Alaminos Campus',
                'campus_code' => 'ALAMINOS',
                'employee_id' => 'ADMIN002',
            ],
            [
                'name' => 'Dr. Ana Garcia',
                'email' => 'admin.urdaneta@psu.edu.ph',
                'campus' => 'PSU Urdaneta Campus',
                'campus_code' => 'URDANETA',
                'employee_id' => 'ADMIN003',
            ],
        ];
        
        foreach ($campusAdmins as $admin) {
            User::create([
                'name' => $admin['name'],
                'email' => $admin['email'],
                'password' => Hash::make('Admin@2025!'),
                'employee_id' => $admin['employee_id'],
                'department' => 1, // Office of the President
                'position' => 'Campus Administrator',
                'role' => 'admin',
                'campus' => $admin['campus'],
                'campus_code' => $admin['campus_code'],
                'phone_number' => '+63-917-123-4567',
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
            ]);
        }
        
        // Creator/Editor Accounts
        $creators = [
            [
                'name' => 'Prof. Carlos Mendoza',
                'email' => 'creator.lingayen@psu.edu.ph',
                'campus' => 'PSU Lingayen Campus',
                'campus_code' => 'LINGAYEN',
                'employee_id' => 'CREATOR001',
            ],
            [
                'name' => 'Prof. Elena Rodriguez',
                'email' => 'creator.alaminos@psu.edu.ph',
                'campus' => 'PSU Alaminos Campus',
                'campus_code' => 'ALAMINOS',
                'employee_id' => 'CREATOR002',
            ],
            [
                'name' => 'Prof. Miguel Torres',
                'email' => 'creator.urdaneta@psu.edu.ph',
                'campus' => 'PSU Urdaneta Campus',
                'campus_code' => 'URDANETA',
                'employee_id' => 'CREATOR003',
            ],
        ];
        
        foreach ($creators as $creator) {
            User::create([
                'name' => $creator['name'],
                'email' => $creator['email'],
                'password' => Hash::make('Creator@2025!'),
                'employee_id' => $creator['employee_id'],
                'department' => 8, // Planning and Development Office
                'position' => 'Planning Officer',
                'role' => 'creator_editor',
                'campus' => $creator['campus'],
                'campus_code' => $creator['campus_code'],
                'phone_number' => '+63-917-123-4567',
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
            ]);
        }

        // Planning Coordinator (for template assignment dropdown)
        User::create([
            'name' => 'Planning Coordinator Lingayen',
            'email' => 'planning.lingayen@psu.edu.ph',
            'password' => Hash::make('Planning@2025!'),
            'employee_id' => 'PC001',
            'department' => 8, // Planning and Development Office
            'position' => 'Planning Coordinator',
            'role' => 'planning_coordinator',
            'campus' => 'PSU Lingayen Campus',
            'campus_code' => 'LINGAYEN',
            'phone_number' => '+63-917-123-4567',
            'is_active' => true,
            'is_approved' => true,
            'approved_at' => now(),
        ]);
        
        // Test Account
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'employee_id' => 'TEST001',
            'department' => 8, // Planning and Development Office
            'position' => 'Test User',
            'role' => 'creator_editor',
            'campus' => 'PSU Lingayen Campus',
            'campus_code' => 'LINGAYEN',
            'phone_number' => '+63-917-123-4567',
            'is_active' => true,
            'is_approved' => true,
            'approved_at' => now(),
        ]);
    }
}
