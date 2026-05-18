<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessagingService
{
    /**
     * Determine whether $sender is allowed to message $recipient.
     *
     * Rules:
     *   Super Admin  → can message anyone
     *   Planning Coordinator → can message Super Admin or QA Coordinator (admin), NOT other PCs
     *   QA Coordinator (admin) → can message Super Admin or Planning Coordinators, NOT other QAs
     */
    public function canMessage(User $sender, User $recipient): bool
    {
        // Cannot message yourself
        if ($sender->id === $recipient->id) {
            return false;
        }

        // Inactive / unapproved users cannot send messages
        if (!$recipient->is_active || !$recipient->is_approved) {
            return false;
        }

        if (! $sender->is_active || ! $sender->is_approved) {
            return false;
        }

        // Beta testers can reach developers; developers can message any approved user for support.
        if ($sender->isDeveloper() || $recipient->isDeveloper()) {
            return true;
        }

        if ($sender->isSuperAdmin()) {
            return true;
        }

        if ($sender->isPlanningCoordinator()) {
            // May message Super Admin or QA Coordinator only
            return $recipient->isSuperAdmin() || $recipient->isAdmin();
        }

        if ($sender->isAdmin()) {
            // QA Coordinator may message Super Admin or Planning Coordinators
            return $recipient->isSuperAdmin() || $recipient->isPlanningCoordinator();
        }

        if ($sender->isDivisionLevelViewOnly()) {
            // Division accounts may only contact developers via the support channel.
            return $recipient->isDeveloper();
        }

        return false;
    }

    /**
     * Return all users the given user is allowed to start a conversation with.
     */
    public function getMessageableUsers(User $user): Collection
    {
        $query = User::where('id', '!=', $user->id)
                     ->where('is_active', true)
                     ->where('is_approved', true);

        if ($user->isPlanningCoordinator()) {
            $query->where(function ($q) {
                $q->where('role', User::ROLE_SUPER_ADMIN)
                  ->orWhere('role', User::ROLE_ADMIN)
                  ->orWhere('role', User::ROLE_DEVELOPER);
            });
        } elseif ($user->isAdmin()) {
            $query->where(function ($q) {
                $q->where('role', User::ROLE_SUPER_ADMIN)
                  ->orWhere('role', User::ROLE_PLANNING_COORDINATOR)
                  ->orWhere('role', User::ROLE_CREATOR_EDITOR)
                  ->orWhere('role', User::ROLE_DEVELOPER);
            });
        } elseif ($user->isDivisionLevelViewOnly()) {
            $query->where('role', User::ROLE_DEVELOPER);
        }
        // Super Admin & Developer: no additional filter → all active approved users

        return $query->orderBy('name')->get(['id', 'name', 'role', 'position', 'campus']);
    }

    /**
     * Find an existing private conversation between two users,
     * or create a new one.  Enforces permission rules.
     *
     * @throws \InvalidArgumentException when permission denied or duplicate blocked
     */
    public function findOrCreatePrivateConversation(User $initiator, int $recipientId): Conversation
    {
        $recipient = User::findOrFail($recipientId);

        if (!$this->canMessage($initiator, $recipient)) {
            throw new \InvalidArgumentException(
                'You are not permitted to start a conversation with this user.'
            );
        }

        // Check for an existing private conversation between these two users
        $existing = $this->findPrivateConversationBetween($initiator->id, $recipientId);

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($initiator, $recipientId) {
            $conversation = Conversation::create(['type' => 'private']);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $initiator->id,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $recipientId,
            ]);

            return $conversation;
        });
    }

    /**
     * Find an existing private (2-person) conversation between two users.
     */
    public function findPrivateConversationBetween(int $userAId, int $userBId): ?Conversation
    {
        return Conversation::where('type', 'private')
            ->whereHas('conversationParticipants', fn ($q) => $q->where('user_id', $userAId))
            ->whereHas('conversationParticipants', fn ($q) => $q->where('user_id', $userBId))
            ->whereRaw(
                '(SELECT COUNT(*) FROM conversation_participants WHERE conversation_id = conversations.id) = 2'
            )
            ->first();
    }

    /**
     * Clear conversation history while keeping the conversation and participants.
     */
    public function deleteConversation(User $user, int $conversationId, bool $forEveryone = false): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (!$conversation->hasParticipant($user->id)) {
            throw new \InvalidArgumentException('You are not a participant of this conversation.');
        }

        DB::transaction(function () use ($conversation) {
            // Delete only message history; keep conversation + participants.
            Message::where('conversation_id', $conversation->id)->delete();
        });
    }

    /**
     * Return all conversations for a user, with the latest message and unread count.
     * Ordered by latest message descending.
     *
     * Uses a plain MAX(id) subquery instead of Laravel's latestOfMany() to stay
     * compatible with MySQL 8+ (which rejects user-defined variables in HAVING).
     */
    public function getConversationsFor(User $user): Collection
    {
        $conversations = Conversation::whereHas(
            'conversationParticipants',
            fn ($q) => $q->where('user_id', $user->id)
        )
        ->with([
            'participants' => fn ($q) => $q->where('users.id', '!=', $user->id)
                                            ->select('users.id', 'users.name', 'users.role', 'users.position', 'users.campus'),
        ])
        ->get();

        if ($conversations->isEmpty()) {
            return $conversations;
        }

        $convIds = $conversations->pluck('id')->all();

        // Fetch the single latest message per conversation using a correlated subquery —
        // this is MySQL 8 compatible and avoids the @variable HAVING issue.
        $latestMessages = Message::whereIn('conversation_id', $convIds)
            ->whereRaw('id = (
                SELECT MAX(m2.id) FROM messages m2
                WHERE m2.conversation_id = messages.conversation_id
            )')
            ->get()
            ->keyBy('conversation_id');

        // Count unread per conversation in one query
        $unreadCounts = Message::whereIn('conversation_id', $convIds)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->selectRaw('conversation_id, COUNT(*) as cnt')
            ->groupBy('conversation_id')
            ->pluck('cnt', 'conversation_id');

        // Attach computed attributes to each conversation
        $conversations->each(function (Conversation $conv) use ($latestMessages, $unreadCounts) {
            $last = $latestMessages->get($conv->id);
            $conv->setRelation('messages', $last ? collect([$last]) : collect());
            $conv->setAttribute('unread_count', (int) ($unreadCounts->get($conv->id, 0)));
        });

        // Sort by latest message id descending (id is auto-increment → mirrors time order)
        return $conversations->sortByDesc(function (Conversation $conv) {
            $last = $conv->messages->first();
            return $last ? $last->id : 0;
        })->values();
    }

    /**
     * Send a message. Enforces:
     *  - Sender must be a participant
     *  - Role permission check still applies
     *  - Message must not be empty
     */
    /**
     * @param  list<array{path: string, name: string, mime: string, size: int}>  $attachments
     */
    public function sendMessage(
        User   $sender,
        int    $conversationId,
        string $messageText,
        ?string $attachmentUrl = null,
        array $attachments = []
    ): Message {
        $messageText = trim($messageText);

        if ($messageText === '' && $attachments === []) {
            throw new \InvalidArgumentException('Add a message or at least one attachment.');
        }

        $conversation = Conversation::findOrFail($conversationId);

        // Sender must be a participant
        if (!$conversation->hasParticipant($sender->id)) {
            throw new \InvalidArgumentException(
                'You are not a participant of this conversation.'
            );
        }

        // Re-check role permission against the other participant
        $otherParticipant = $conversation->participants()
                                          ->where('users.id', '!=', $sender->id)
                                          ->first();

        if ($otherParticipant && !$this->canMessage($sender, $otherParticipant)) {
            throw new \InvalidArgumentException(
                'You are not permitted to send messages in this conversation.'
            );
        }

        $msg = Message::create([
            'conversation_id' => $conversationId,
            'sender_id'       => $sender->id,
            'message'         => $messageText === '' ? '' : $messageText,
            'attachment_url'  => $attachmentUrl,
            'attachments'     => $attachments === [] ? null : $attachments,
            'is_read'         => false,
            'created_at'      => now(),
        ]);

        // Ensure created_at is loaded even if the model instance lacks it
        if (is_null($msg->created_at)) {
            $msg->created_at = now();
        }

        if ($attachments !== []) {
            $msg->update([
                'attachment_url' => route('messaging.messages.attachment', ['message' => $msg->id, 'index' => 0], false),
            ]);
        }

        return $msg->fresh();
    }

    /**
     * Get paginated messages for a conversation (ordered ASC by created_at).
     * Verifies the requesting user is a participant.
     */
    public function getMessages(
        User $user,
        int  $conversationId,
        int  $perPage = 30
    ): LengthAwarePaginator {
        $conversation = Conversation::findOrFail($conversationId);

        if (!$conversation->hasParticipant($user->id)) {
            abort(403, 'You are not a participant of this conversation.');
        }

        return Message::with([
                          'sender:id,name,role,position',
                          'replyTo' => fn ($q) => $q->with('sender:id,name')->withTrashed(),
                      ])
                      ->where('conversation_id', $conversationId)
                      ->orderBy('created_at', 'asc')
                      ->paginate($perPage);
    }

    /**
     * Mark all unread messages in a conversation as read for a given user
     * (only messages NOT sent by that user).
     */
    public function markAsRead(User $user, int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (!$conversation->hasParticipant($user->id)) {
            return;
        }

        Message::where('conversation_id', $conversationId)
               ->where('sender_id', '!=', $user->id)
               ->where('is_read', false)
               ->update(['is_read' => true]);
    }

    /**
     * Total unread message count across all conversations for a user.
     */
    public function totalUnreadCount(User $user): int
    {
        return Message::whereHas('conversation.conversationParticipants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('sender_id', '!=', $user->id)
        ->where('is_read', false)
        ->count();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // MESSAGE ACTIONS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Send a reply to a specific message.
     */
    /**
     * @param  list<array{path: string, name: string, mime: string, size: int}>  $attachments
     */
    public function replyToMessage(
        User   $sender,
        int    $conversationId,
        string $messageText,
        int    $replyToId,
        array $attachments = []
    ): Message {
        // Verify the parent message belongs to the same conversation
        $parent = Message::withTrashed()->where('id', $replyToId)
                         ->where('conversation_id', $conversationId)
                         ->firstOrFail();

        $messageText = trim($messageText);
        if ($messageText === '' && $attachments === []) {
            throw new \InvalidArgumentException('Add a message or at least one attachment.');
        }

        $conversation = Conversation::findOrFail($conversationId);
        if (!$conversation->hasParticipant($sender->id)) {
            throw new \InvalidArgumentException('You are not a participant of this conversation.');
        }

        $msg = Message::create([
            'conversation_id' => $conversationId,
            'sender_id'       => $sender->id,
            'message'         => $messageText === '' ? '' : $messageText,
            'attachment_url'  => null,
            'attachments'     => $attachments === [] ? null : $attachments,
            'is_read'         => false,
            'reply_to_id'     => $parent->id,
            'created_at'      => now(),
        ]);

        if (is_null($msg->created_at)) {
            $msg->created_at = now();
        }

        if ($attachments !== []) {
            $msg->update([
                'attachment_url' => route('messaging.messages.attachment', ['message' => $msg->id, 'index' => 0], false),
            ]);
        }

        return $msg->fresh();
    }

    /**
     * Edit a message. Only the original sender may edit.
     */
    public function editMessage(User $user, int $messageId, string $newText): Message
    {
        $msg = Message::findOrFail($messageId);

        if ($msg->sender_id !== $user->id) {
            throw new \InvalidArgumentException('You can only edit your own messages.');
        }

        $newText = trim($newText);
        if ($newText === '') {
            throw new \InvalidArgumentException('Message cannot be empty.');
        }

        $msg->update([
            'message'   => $newText,
            'edited_at' => now(),
        ]);

        return $msg->fresh();
    }

    /**
     * Soft-delete a message. Only the original sender may delete.
     */
    public function deleteMessage(User $user, int $messageId): void
    {
        $msg = Message::findOrFail($messageId);

        if ($msg->sender_id !== $user->id) {
            throw new \InvalidArgumentException('You can only delete your own messages.');
        }

        $msg->delete(); // SoftDeletes
    }

    /**
     * Forward a message to a different conversation.
     */
    public function forwardMessage(User $sender, int $messageId, int $targetConversationId): Message
    {
        $original     = Message::withTrashed()->findOrFail($messageId);
        $targetConv   = Conversation::findOrFail($targetConversationId);

        if (!$targetConv->hasParticipant($sender->id)) {
            throw new \InvalidArgumentException('You are not a participant of the target conversation.');
        }

        // Re-check role permission for the target conversation
        $otherParticipant = $targetConv->participants()
                                       ->where('users.id', '!=', $sender->id)
                                       ->first();

        if ($otherParticipant && !$this->canMessage($sender, $otherParticipant)) {
            throw new \InvalidArgumentException('You are not permitted to forward to this conversation.');
        }

        $copiedAttachments = $this->copyAttachmentFiles($original->attachments ?? []);

        $msg = Message::create([
            'conversation_id' => $targetConversationId,
            'sender_id'       => $sender->id,
            'message'         => $original->message,
            'attachment_url'  => null,
            'attachments'     => $copiedAttachments === [] ? null : $copiedAttachments,
            'is_read'         => false,
            'created_at'      => now(),
        ]);

        if (is_null($msg->created_at)) {
            $msg->created_at = now();
        }

        if ($copiedAttachments !== []) {
            $msg->update([
                'attachment_url' => route('messaging.messages.attachment', ['message' => $msg->id, 'index' => 0], false),
            ]);
        }

        return $msg->fresh();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $attachments
     * @return list<array{path: string, name: string, mime: string, size: int}>
     */
    private function copyAttachmentFiles(?array $attachments): array
    {
        if (! is_array($attachments) || $attachments === []) {
            return [];
        }

        $disk = Storage::disk('public');
        $out = [];

        foreach ($attachments as $att) {
            if (! is_array($att) || empty($att['path']) || ! $disk->exists($att['path'])) {
                continue;
            }
            $from = $att['path'];
            $ext = pathinfo($from, PATHINFO_EXTENSION);
            $newPath = 'messaging/'.Str::uuid()->toString().($ext !== '' ? '.'.$ext : '');
            if (! $disk->copy($from, $newPath)) {
                continue;
            }
            $out[] = [
                'path' => $newPath,
                'name' => (string) ($att['name'] ?? basename($newPath)),
                'mime' => (string) ($att['mime'] ?? 'application/octet-stream'),
                'size' => (int) ($att['size'] ?? $disk->size($newPath)),
            ];
        }

        return $out;
    }

    /**
     * Pin a message. Any participant may pin.
     */
    public function pinMessage(User $user, int $messageId): Message
    {
        $msg = Message::findOrFail($messageId);
        $conversation = Conversation::findOrFail($msg->conversation_id);

        if (!$conversation->hasParticipant($user->id)) {
            throw new \InvalidArgumentException('You are not a participant of this conversation.');
        }

        $msg->update(['is_pinned' => true]);
        return $msg->fresh();
    }

    /**
     * Unpin a message. Any participant may unpin.
     */
    public function unpinMessage(User $user, int $messageId): void
    {
        $msg = Message::findOrFail($messageId);
        $conversation = Conversation::findOrFail($msg->conversation_id);

        if (!$conversation->hasParticipant($user->id)) {
            throw new \InvalidArgumentException('You are not a participant of this conversation.');
        }

        $msg->update(['is_pinned' => false]);
    }

    /**
     * Get all pinned messages for a conversation.
     */
    public function getPinnedMessages(User $user, int $conversationId): \Illuminate\Database\Eloquent\Collection
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (!$conversation->hasParticipant($user->id)) {
            throw new \InvalidArgumentException('You are not a participant of this conversation.');
        }

        return Message::with('sender:id,name')
                      ->where('conversation_id', $conversationId)
                      ->where('is_pinned', true)
                      ->orderBy('created_at', 'asc')
                      ->get();
    }
}
