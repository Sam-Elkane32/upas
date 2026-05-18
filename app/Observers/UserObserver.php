<?php

namespace App\Observers;

use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Automatically wire a Super Admin ↔ new user conversation
     * whenever any messageable user account is created.
     *
     * Messageable roles: admin (QA Coordinator), planning_coordinator, creator_editor
     * Excluded:          view_only (CED) — they cannot use messaging
     */
    public function created(User $newUser): void
    {
        // Skip non-messageable roles
        if ($newUser->isViewOnly()) {
            return;
        }

        // Only create conversations for roles that participate in messaging
        $messagingRoles = [
            User::ROLE_ADMIN,
            User::ROLE_PLANNING_COORDINATOR,
            User::ROLE_CREATOR_EDITOR,
        ];

        if (!in_array($newUser->role, $messagingRoles, true)) {
            return;
        }

        $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();

        if (!$superAdmin) {
            Log::warning("[UserObserver] No Super Admin found — skipping auto-conversation for user {$newUser->id}.");
            return;
        }

        // Don't create a conversation between Super Admin and themselves
        if ($superAdmin->id === $newUser->id) {
            return;
        }

        try {
            app(MessagingService::class)->findOrCreatePrivateConversation($superAdmin, $newUser->id);
            Log::info("[UserObserver] Auto-conversation created between Super Admin ({$superAdmin->id}) and new user {$newUser->id} ({$newUser->name}).");
        } catch (\Throwable $e) {
            Log::error("[UserObserver] Failed to auto-create conversation for user {$newUser->id}: " . $e->getMessage());
        }
    }
}
