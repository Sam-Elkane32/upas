<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDivisionViewOnlyAccounts extends Command
{
    protected $signature = 'uaps:create-division-accounts
                            {--force : Create/update accounts even if they already exist}
                            {--all-departments : Include non-office departments too}
                            {--prune-extra : Remove division.* accounts not in canonical division list}';

    protected $description = 'Create division-level view-only accounts (OP, OVPAA, OVPA, etc.)';

    public function handle(): int
    {
        $this->info('Creating Division (view-only) accounts...');

        $defaultPassword = Setting::get('default_password', 'UPAS@2025!');
        // Canonical division list from Super Admin "Select Division" UI.
        $divisionDefinitions = [
            ['code' => 'OP', 'slug' => 'op', 'name' => 'Office of the President'],
            ['code' => 'OVPAFM', 'slug' => 'ovpafm', 'name' => 'Office of the Vice President for Administration and Finance Management'],
            ['code' => 'OVPASS', 'slug' => 'ovpass', 'name' => 'Office of the Vice President for Academic and Student Services'],
            ['code' => 'OVPREI', 'slug' => 'ovprei', 'name' => 'Office of the Vice President for Research, Extension & Innovation'],
            ['code' => 'OVPQA', 'slug' => 'ovpqa', 'name' => 'Office of the Vice President for Quality Assurance'],
            ['code' => 'OVPLIA', 'slug' => 'ovplia', 'name' => 'Office of the Vice President for Local & International Affairs'],
        ];
        $departments = Department::query()->orderBy('name')->get();

        if ($departments->isEmpty()) {
            $this->warn('No matching divisions/departments found.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        $canonicalEmails = [];
        foreach ($divisionDefinitions as $division) {
            $code = $division['code'];
            $emailSlug = $division['slug'];
            $department = $departments->first(function ($dept) use ($code, $division) {
                $dCode = strtoupper((string) ($dept->code ?? ''));
                $dName = strtoupper((string) ($dept->name ?? ''));
                return $dCode === $code || $dName === strtoupper($division['name']);
            });

            $email = "division.{$emailSlug}@psu.edu.ph";
            $canonicalEmails[] = $email;
            $name = ($code ?: 'DIVISION') . ' Division';

            $existingQuery = User::where('email', $email)
                ->where('role', User::ROLE_VIEW_ONLY);

            if (!$this->option('force') && $existingQuery->exists()) {
                $this->line(" - {$division['name']}: already exists ({$email}), skipping.");
                $skipped++;
                continue;
            }

            $user = $existingQuery->first();
            if (!$user) {
                $user = new User();
            }

            do {
                $employeeId = 'EMP-' . date('Ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            } while (!$user->exists && User::where('employee_id', $employeeId)->exists());

            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make($defaultPassword);
            if (!$user->employee_id) {
                $user->employee_id = $employeeId;
            }
            $user->department = $department?->id;
            $user->position = 'Division CED';
            $user->role = User::ROLE_VIEW_ONLY;
            // Keep division users separated from campus users.
            $user->campus = 'Division Offices';
            $user->campus_code = 'DIVISION';
            $user->is_active = true;
            $user->is_approved = true;
            $user->approved_at = now();
            $user->approved_by = null;
            $user->save();

            $this->info(" - {$division['name']}: created/updated ({$name}, {$email})");
            $created++;
        }

        $this->info("Done. Created/updated {$created} account(s), skipped {$skipped}.");

        if ($this->option('prune-extra')) {
            $deleted = User::query()
                ->where('role', User::ROLE_VIEW_ONLY)
                ->where('email', 'like', 'division.%@psu.edu.ph')
                ->whereNotIn('email', $canonicalEmails)
                ->delete();
            $this->info("Pruned {$deleted} extra division account(s) outside canonical list.");
        }

        $this->info("Default password: {$defaultPassword}");

        return self::SUCCESS;
    }
}

