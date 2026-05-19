<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates two Messages-only "developer" accounts for beta feedback.
 * Safe to run on existing databases (updateOrCreate by email).
 *
 * Default logins (override via .env):
 *   UAPS_DEVELOPER_1_EMAIL, UAPS_DEVELOPER_1_PASSWORD
 *   UAPS_DEVELOPER_2_EMAIL, UAPS_DEVELOPER_2_PASSWORD
 */
class DeveloperAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'UAPS Developer 1',
                'email' => env('UAPS_DEVELOPER_1_EMAIL', 'developer1@psu.edu.ph'),
                'password' => env('UAPS_DEVELOPER_1_PASSWORD', 'DevTeam@2025!'),
                'employee_id' => 'DEV001',
            ],
            [
                'name' => 'UAPS Developer 2',
                'email' => env('UAPS_DEVELOPER_2_EMAIL', 'developer2@psu.edu.ph'),
                'password' => env('UAPS_DEVELOPER_2_PASSWORD', 'DevTeam@2025!'),
                'employee_id' => 'DEV002',
            ],
        ];

        foreach ($accounts as $row) {
            User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => $row['password'],
                    'employee_id' => $row['employee_id'],
                    'department' => 9,
                    'position' => 'System Developer',
                    'role' => User::ROLE_DEVELOPER,
                    'campus' => 'All Campuses',
                    'campus_code' => 'LINGAYEN',
                    'phone_number' => null,
                    'is_active' => true,
                    'is_approved' => true,
                    'approved_at' => now(),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
