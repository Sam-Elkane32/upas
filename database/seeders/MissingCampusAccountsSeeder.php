<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MissingCampusAccountsSeeder extends Seeder
{
    /**
     * Create QA Coordinator and Planning Coordinator accounts for campuses
     * that currently have no users.
     */
    public function run(): void
    {
        $defaultPassword = Setting::get('default_password', 'UPAS@2025!');
        $superAdmin = User::where('role', 'super_admin')->first();

        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $created = 0;

        foreach ($campuses as $campus) {
            $existingCount = User::where('campus_code', $campus->code)
                ->where('role', '!=', 'super_admin')
                ->count();

            if ($existingCount > 0) {
                continue;
            }

            $code = $campus->code;
            $codeLower = strtolower($code);
            $campusName = $campus->name;

            // QA Coordinator
            $qaEmail = "admin.{$codeLower}@psu.edu.ph";
            if (!User::where('email', $qaEmail)->exists()) {
                User::create([
                    'name' => "{$code} QA Coordinator",
                    'email' => $qaEmail,
                    'password' => Hash::make($defaultPassword),
                    'employee_id' => $this->uniqueEmployeeId(),
                    'position' => 'QA Coordinator',
                    'role' => 'admin',
                    'campus_code' => $campus->code,
                    'campus' => $campusName,
                    'is_active' => true,
                    'is_approved' => true,
                    'approved_by' => $superAdmin?->id,
                    'approved_at' => now(),
                ]);
                $created++;
            }

            // Planning Coordinator
            $pcEmail = "creator.{$codeLower}@psu.edu.ph";
            if (!User::where('email', $pcEmail)->exists()) {
                User::create([
                    'name' => "{$code} Planning Coordinator",
                    'email' => $pcEmail,
                    'password' => Hash::make($defaultPassword),
                    'employee_id' => $this->uniqueEmployeeId(),
                    'position' => 'Planning Coordinator',
                    'role' => 'creator_editor',
                    'campus_code' => $campus->code,
                    'campus' => $campusName,
                    'is_active' => true,
                    'is_approved' => true,
                    'approved_by' => $superAdmin?->id,
                    'approved_at' => now(),
                ]);
                $created++;
            }
        }

        $this->command->info("Created {$created} user(s) for campuses that had no accounts.");
    }

    private function uniqueEmployeeId(): string
    {
        do {
            $id = 'EMP-' . date('Ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (User::where('employee_id', $id)->exists());
        return $id;
    }
}
