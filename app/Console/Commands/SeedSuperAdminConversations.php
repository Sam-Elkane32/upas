<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Console\Command;

class SeedSuperAdminConversations extends Command
{
    protected $signature   = 'messaging:seed-super-admin-conversations';
    protected $description = 'Create a conversation between the Super Admin and every existing messageable user.';

    public function handle(MessagingService $service): int
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        if (!$superAdmin) {
            $this->error('No Super Admin user found.');
            return self::FAILURE;
        }

        $users = User::where('id', '!=', $superAdmin->id)
                     ->where('is_active', true)
                     ->where('is_approved', true)
                     ->whereIn('role', ['admin', 'planning_coordinator', 'creator_editor'])
                     ->get();

        $created  = 0;
        $existing = 0;

        foreach ($users as $user) {
            try {
                $conv = $service->findOrCreatePrivateConversation($superAdmin, $user->id);
                // findOrCreatePrivateConversation returns the existing one if found
                $existing++;
            } catch (\Throwable $e) {
                $this->warn("Skipped user {$user->id} ({$user->name}): {$e->getMessage()}");
                continue;
            }
            $created++;
        }

        $this->info("Done. Conversations ensured for {$created} user(s) (out of {$users->count()} messageable users).");
        return self::SUCCESS;
    }
}
