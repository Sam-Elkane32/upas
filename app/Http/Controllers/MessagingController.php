<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\SupportReport;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class MessagingController extends Controller
{
    public function __construct(private readonly MessagingService $messagingService) {}

    private function assertMessagingAccess(): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->isAdmin() && ! $user->isPlanningCoordinator() && ! $user->isDeveloper() && ! $user->isDivisionLevelViewOnly()) {
            abort(403, 'Messaging is not available for your account type.');
        }
    }

    /**
     * Serve a message attachment (auth + conversation participant).
     * Uses relative URLs from the client so images/PDFs work when using LAN IP (not APP_URL localhost).
     * Images: inline. Other files: download.
     */
    public function downloadAttachment(Request $request, Message $message, int $index)
    {
        $this->assertMessagingAccess();

        if ($message->trashed()) {
            abort(404);
        }

        $user = Auth::user();
        $message->loadMissing('conversation');
        $conversation = $message->conversation;
        if (! $conversation || ! $conversation->hasParticipant($user->id)) {
            abort(403, 'You cannot access this attachment.');
        }

        $list = $message->attachments;
        if (! is_array($list) || ! array_key_exists($index, $list)) {
            abort(404);
        }

        $att = $list[$index];
        if (! is_array($att)) {
            abort(404);
        }

        $relativePath = (string) ($att['path'] ?? '');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($relativePath)) {
            abort(404);
        }

        $absolutePath = $disk->path($relativePath);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $mime = (string) ($att['mime'] ?? '');
        if ($mime === '') {
            $mime = @mime_content_type($absolutePath) ?: 'application/octet-stream';
        }

        $downloadName = (string) ($att['name'] ?? basename($relativePath));
        $downloadName = str_replace(["\0", "\r", "\n", '"'], '', $downloadName);

        $forceDownload = $request->boolean('download');

        if (str_starts_with($mime, 'image/') && ! $forceDownload) {
            return response()->file($absolutePath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            ]);
        }

        return response()->download($absolutePath, $downloadName, [
            'Content-Type' => $mime,
        ]);
    }

    /** Helper: serialize a Message to array for JSON responses */
    private function serializeMessage(Message $msg, int $authUserId): array
    {
        $createdAt = $msg->created_at ?? now();
        return [
            'id'          => $msg->id,
            'message'     => $msg->trashed() ? null : $msg->message,
            'deleted'     => $msg->trashed(),
            'sender_id'   => $msg->sender_id,
            'sender'      => $msg->sender ? ['id' => $msg->sender->id, 'name' => $msg->sender->name] : null,
            'is_mine'     => $msg->sender_id === $authUserId,
            'is_read'     => (bool) $msg->is_read,
            'is_pinned'   => (bool) $msg->is_pinned,
            'edited_at'   => $msg->edited_at ? $msg->edited_at->toISOString() : null,
            'created_at'  => $createdAt->toISOString(),
            'attachments' => $msg->trashed() ? [] : $this->serializeAttachmentsForApi($msg),
            'reply_to'    => $msg->replyTo ? [
                'id'          => $msg->replyTo->id,
                'message'     => $msg->replyTo->trashed() ? null : $msg->replyTo->message,
                'deleted'     => $msg->replyTo->trashed(),
                'sender_name' => $msg->replyTo->sender ? $msg->replyTo->sender->name : 'Unknown',
            ] : null,
        ];
    }

    /**
     * @return list<array{name: string, mime: string, url: string, is_image: bool, size: int, size_label: string}>
     */
    private function serializeAttachmentsForApi(Message $msg): array
    {
        $list = $msg->attachments;
        if (! is_array($list) || $list === []) {
            return [];
        }
        $disk = Storage::disk('public');
        $out = [];
        foreach ($list as $idx => $a) {
            if (! is_array($a) || empty($a['path']) || ! $disk->exists($a['path'])) {
                continue;
            }
            $mime = (string) ($a['mime'] ?? 'application/octet-stream');
            $size = (int) ($a['size'] ?? 0);
            if ($size <= 0) {
                try {
                    $size = (int) $disk->size($a['path']);
                } catch (\Throwable $e) {
                    $size = 0;
                }
            }
            $out[] = [
                'name'       => (string) ($a['name'] ?? basename($a['path'])),
                'mime'       => $mime,
                // Relative path: browser uses current host (e.g. 192.168.x.x) instead of APP_URL
                'url'        => route('messaging.messages.attachment', ['message' => $msg->id, 'index' => (int) $idx], false),
                'is_image'   => str_starts_with($mime, 'image/'),
                'size'       => $size,
                'size_label' => $size > 0 ? $this->formatFileSizeForMessaging($size) : '',
            ];
        }

        return $out;
    }

    private function formatFileSizeForMessaging(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return number_format($bytes / 1048576, 2).' MB';
    }

    /**
     * @return list<array{path: string, name: string, mime: string, size: int}>
     */
    private function storeUploadedAttachments(Request $request): array
    {
        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = array_filter([$files]);
        }
        $files = array_slice($files, 0, 5);

        $out = [];
        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $path = $file->store('messaging', 'public');
            $out[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $mime,
                'size' => (int) $file->getSize(),
            ];
        }

        return $out;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // MAIN PAGE
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->assertMessagingAccess();

        $user = Auth::user();

        // Developer accounts do not use the staff "Report Form"; they only manage tickets under Reports & Concerns.
        if ($user->isDeveloper() && $request->query('audience') === 'developers' && ! $request->filled('conversation')) {
            return redirect()->route('messaging.developer-tickets.index', ['audience' => 'developers']);
        }

        $conversations = $this->messagingService->getConversationsFor($user);
        $activeConv    = null;
        $messages      = null;
        $otherUser     = null;
        $pinnedMessages = collect();

        $convId = $request->query('conversation');
        if ($convId) {
            try {
                $activeConv = Conversation::findOrFail($convId);

                if (!$activeConv->hasParticipant($user->id)) {
                    abort(403, 'You are not a participant of this conversation.');
                }

                $messages = $this->messagingService->getMessages($user, $activeConv->id, 50);
                $otherUser = $activeConv->participants()
                                        ->where('users.id', '!=', $user->id)
                                        ->first();

                $pinnedMessages = $this->messagingService->getPinnedMessages($user, $activeConv->id);
                $this->messagingService->markAsRead($user, $activeConv->id);
                $conversations = $this->messagingService->getConversationsFor($user);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                abort(404, 'Conversation not found.');
            }
        }

        $messageableUsers = $this->messagingService->getMessageableUsers($user);

        return view('messaging.index', compact(
            'conversations', 'activeConv', 'messages',
            'otherUser', 'messageableUsers', 'pinnedMessages'
        ));
    }

    /**
     * Developer Support — repair tickets list (all tickets for developers; own tickets for other roles).
     */
    public function developerTickets(Request $request)
    {
        $this->assertMessagingAccess();

        if ($request->query('audience') !== 'developers') {
            return redirect()->route('messaging.developer-tickets.index', ['audience' => 'developers']);
        }

        $user = Auth::user();

        if ($user->isDeveloper()) {
            $supportReportsInbox = SupportReport::with(['user', 'repairTicket.assignedTo'])
                ->latest()
                ->get();
            $supportReportsMine = collect();
        } else {
            $supportReportsInbox = collect();
            $supportReportsMine = SupportReport::with(['repairTicket'])
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        }

        return view('messaging.developer-tickets.index', compact('supportReportsInbox', 'supportReportsMine'));
    }

    /**
     * Database notifications for developer accounts (repair ticket submissions).
     * Campus-user notification routes are not available to the developer role.
     */
    public function developerNotifications(Request $request)
    {
        $this->assertMessagingAccess();

        if ($request->query('audience') !== 'developers') {
            return redirect()->route('messaging.developer-notifications.index', ['audience' => 'developers']);
        }

        $user = Auth::user();
        if (! $user->isDeveloper()) {
            abort(403, 'Only developer accounts can view ticket alerts.');
        }

        $notifications = $user->notifications()->latest()->paginate(20);

        return view('messaging.developer-notifications.index', compact('notifications'));
    }

    /**
     * Mark a notification read and go to the repair ticket (or tickets list).
     */
    public function developerNotificationOpen(string $id)
    {
        $this->assertMessagingAccess();

        $user = Auth::user();
        if (! $user->isDeveloper()) {
            abort(403);
        }

        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();
        $data = $notification->data;
        $url = is_array($data) && ! empty($data['url'])
            ? $data['url']
            : route('messaging.developer-tickets.index', ['audience' => 'developers']);

        return redirect()->to($url);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CONVERSATIONS
    // ──────────────────────────────────────────────────────────────────────────

    public function startConversation(Request $request)
    {
        $this->assertMessagingAccess();
        $request->validate(['recipient_id' => ['required', 'integer', 'exists:users,id']]);

        try {
            $conversation = $this->messagingService->findOrCreatePrivateConversation(
                Auth::user(),
                (int) $request->input('recipient_id')
            );
        } catch (\InvalidArgumentException $e) {
            return $request->wantsJson()
                ? response()->json(['error' => $e->getMessage()], 403)
                : back()->withErrors(['recipient_id' => $e->getMessage()]);
        }

        return $request->wantsJson()
            ? response()->json(['conversation_id' => $conversation->id])
            : redirect()->route('messaging.index', ['conversation' => $conversation->id]);
    }

    public function deleteConversation(Request $request, int $id)
    {
        $this->assertMessagingAccess();
        $forEveryone = filter_var($request->input('for_everyone', false), FILTER_VALIDATE_BOOL);

        try {
            $this->messagingService->deleteConversation(Auth::user(), $id, $forEveryone);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SEND & REPLY
    // ──────────────────────────────────────────────────────────────────────────

    public function sendMessage(Request $request, int $conversationId)
    {
        $this->assertMessagingAccess();

        $request->validate([
            'message'       => ['nullable', 'string', 'max:5000'],
            'reply_to_id'   => ['nullable', 'integer', 'exists:messages,id'],
            'attachments'   => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']),
            ],
        ]);

        $stored = $this->storeUploadedAttachments($request);
        $text = trim((string) $request->input('message', ''));

        if ($text === '' && $stored === []) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Add a message or at least one attachment.'], 422)
                : back()->withErrors(['message' => 'Add a message or at least one attachment.']);
        }

        $sender   = Auth::user();
        $replyToId = $request->input('reply_to_id');

        try {
            if ($replyToId) {
                $msg = $this->messagingService->replyToMessage(
                    $sender, $conversationId, $text, (int) $replyToId, $stored
                );
            } else {
                $msg = $this->messagingService->sendMessage(
                    $sender, $conversationId, $text, null, $stored
                );
            }
        } catch (\InvalidArgumentException $e) {
            return $request->wantsJson()
                ? response()->json(['error' => $e->getMessage()], 422)
                : back()->withErrors(['message' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            $msg->load(['sender:id,name,role,position', 'replyTo.sender:id,name']);
            return response()->json(['success' => true, 'message' => $this->serializeMessage($msg, $sender->id)]);
        }

        return redirect()->route('messaging.index', ['conversation' => $conversationId]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET MESSAGES (poll)
    // ──────────────────────────────────────────────────────────────────────────

    public function getMessages(Request $request, int $conversationId)
    {
        $this->assertMessagingAccess();

        $user      = Auth::user();
        $paginator = $this->messagingService->getMessages($user, $conversationId, 50);

        $items = $paginator->getCollection()->map(
            fn ($m) => $this->serializeMessage($m, $user->id)
        );

        return response()->json([
            'data'         => $items,
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // EDIT
    // ──────────────────────────────────────────────────────────────────────────

    public function editMessage(Request $request, int $messageId)
    {
        $this->assertMessagingAccess();
        $request->validate(['message' => ['required', 'string', 'max:5000']]);

        try {
            $msg = $this->messagingService->editMessage(
                Auth::user(), $messageId, $request->input('message')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $msg->load('sender:id,name');
        return response()->json(['success' => true, 'message' => $this->serializeMessage($msg, Auth::id())]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DELETE
    // ──────────────────────────────────────────────────────────────────────────

    public function deleteMessage(int $messageId)
    {
        $this->assertMessagingAccess();

        try {
            $this->messagingService->deleteMessage(Auth::user(), $messageId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORWARD
    // ──────────────────────────────────────────────────────────────────────────

    public function forwardMessage(Request $request, int $messageId)
    {
        $this->assertMessagingAccess();
        $request->validate(['conversation_id' => ['required', 'integer', 'exists:conversations,id']]);

        try {
            $msg = $this->messagingService->forwardMessage(
                Auth::user(), $messageId, (int) $request->input('conversation_id')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $msg->load('sender:id,name');
        return response()->json(['success' => true, 'message' => $this->serializeMessage($msg, Auth::id())]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PIN / UNPIN
    // ──────────────────────────────────────────────────────────────────────────

    public function pinMessage(int $messageId)
    {
        $this->assertMessagingAccess();

        try {
            $msg = $this->messagingService->pinMessage(Auth::user(), $messageId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $msg->load('sender:id,name');
        return response()->json(['success' => true, 'message' => $this->serializeMessage($msg, Auth::id())]);
    }

    public function unpinMessage(int $messageId)
    {
        $this->assertMessagingAccess();

        try {
            $this->messagingService->unpinMessage(Auth::user(), $messageId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // MARK AS READ / UNREAD COUNT / USERS
    // ──────────────────────────────────────────────────────────────────────────

    public function markAsRead(int $conversationId)
    {
        $this->assertMessagingAccess();
        $this->messagingService->markAsRead(Auth::user(), $conversationId);
        return response()->json(['success' => true]);
    }

    public function unreadCount()
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isPlanningCoordinator() && !$user->isDivisionLevelViewOnly()) {
            return response()->json(['count' => 0]);
        }
        return response()->json(['count' => $this->messagingService->totalUnreadCount($user)]);
    }

    public function messageableUsers()
    {
        $this->assertMessagingAccess();
        return response()->json($this->messagingService->getMessageableUsers(Auth::user()));
    }
}
