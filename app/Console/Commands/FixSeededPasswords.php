<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class FixSeededPasswords extends Command
{
    protected $signature = 'upas:fix-seeded-passwords';

    protected $description = 'Reset demo account passwords (fixes double-hashed passwords from old seeders)';

    public function handle(): int
    {
        $accounts = [
            'superadmin@psu.edu.ph' => 'UAPS@2025!',
            'admin.lingayen@psu.edu.ph' => 'Admin@2025!',
            'creator.lingayen@psu.edu.ph' => 'Creator@2025!',
            'planning.lingayen@psu.edu.ph' => 'Planning@2025!',
            'test@example.com' => 'password',
            'developer1@psu.edu.ph' => 'DevTeam@2025!',
            'developer2@psu.edu.ph' => 'DevTeam@2025!',
        ];

        foreach ($accounts as $email => $password) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->warn("Missing: {$email}");

                continue;
            }

            $user->password = $password;
            $user->save();
            $this->info("Updated: {$email}");
        }

        $this->newLine();
        $this->info('Total users in database: '.User::count());

        return self::SUCCESS;
    }
}
