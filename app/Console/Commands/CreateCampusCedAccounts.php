<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campus;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;

class CreateCampusCedAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uaps:create-ced-accounts
                            {--force : Create accounts even if a CED user already exists for a campus}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create (Campus) CED view-only accounts for all active campuses';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating Campus CED (view-only) accounts...');

        $defaultPassword = Setting::get('default_password', 'UPAS@2025!');

        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        if ($campuses->isEmpty()) {
            $this->warn('No active campuses found. Nothing to do.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($campuses as $campus) {
            $campusCode = $campus->code ?: $campus->name;

            // Build the canonical CED username, e.g. "LINGAYEN CED"
            $sanitizedCode = preg_replace('/[^A-Za-z0-9]/', '_', trim($campusCode));
            $sanitizedCode = strtoupper($sanitizedCode);
            $sanitizedCode = preg_replace('/_+/', '_', trim($sanitizedCode, '_')) ?: strtoupper($campusCode);
            $userName = $sanitizedCode . ' CED';

            $existingQuery = User::where('campus_code', $campus->code)
                ->where('name', $userName)
                ->where('role', User::ROLE_VIEW_ONLY);

            if (!$this->option('force') && $existingQuery->exists()) {
                $this->line(" - {$campus->name}: CED user already exists ({$userName}), skipping.");
                $skipped++;
                continue;
            }

            // Generate unique employee ID similar to UserController@store
            do {
                $employeeId = 'EMP-' . date('Ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            } while (User::where('employee_id', $employeeId)->exists());

            // Either re-use existing record or create new one
            $user = $existingQuery->first();
            if (!$user) {
                $user = new User();
            }

            $user->name = $userName;
            $user->email = strtolower($sanitizedCode) . '.ced@psu.edu.ph';
            $user->password = Hash::make($defaultPassword);
            $user->employee_id = $employeeId;
            $user->position = 'Campus CED';
            $user->role = User::ROLE_VIEW_ONLY;
            $user->campus_code = $campus->code;
            $user->campus = $campus->name;
            $user->is_active = true;
            $user->is_approved = true;
            $user->approved_by = null;
            $user->approved_at = now();
            $user->save();

            $this->info(" - {$campus->name}: CED user created/updated ({$userName}, {$user->email})");
            $created++;
        }

        $this->info("Done. Created/updated {$created} CED account(s), skipped {$skipped} campus(es).");
        $this->info("Default password: {$defaultPassword}");

        return self::SUCCESS;
    }
}

