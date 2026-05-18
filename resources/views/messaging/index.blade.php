<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    @php
        $authUser  = Auth::user();
        $isDeveloperSupportMode = request()->query('audience') === 'developers';
        $roleLabel = $authUser->isSuperAdmin() ? 'Super Admin'
            : ($authUser->isAdmin() ? 'QA Coordinator' : ($authUser->isDeveloper() ? 'Developer' : 'Planning Coordinator'));
        $developerRecipients = $messageableUsers->filter(fn ($u) => $u->isDeveloper())->values();
        $selectableUsers = $isDeveloperSupportMode ? $developerRecipients : $messageableUsers;
        $supportDefaultRecipientId = optional($developerRecipients->first())->id;
        $conversationsForDisplay = $isDeveloperSupportMode
            ? $conversations->filter(function ($conv) {
                $other = $conv->participants->first();
                return $other && $other->isDeveloper();
            })->values()
            : $conversations;
    @endphp

    <style>
        /* Keep scroll inside chat panels, not the whole page */
        body.messaging-page {
            overflow: hidden;
        }
        /*
          Image lightbox is inside <main>; the sidebar is a sibling flex item with z-30.
          Without this, the whole main column stacks below the sidebar and the black overlay
          never covers the rail (fixed #sidebar stays visually on top).
        */
        body.messaging-page.img-lightbox-open #sidebar {
            z-index: 0;
        }
        body.messaging-page.img-lightbox-open #main-content-wrap {
            position: relative;
            z-index: 40;
        }
        body.messaging-page.developer-support-fixed {
            overflow: hidden;
        }
        body.messaging-page.developer-support-fixed #messaging-shell {
            height: calc(100vh - 3rem) !important;
            overflow: hidden;
        }
    </style>

    <div id="messaging-shell" class="min-h-0 flex flex-col overflow-hidden" style="height: calc(100vh - 3rem);">

        {{-- Page header --}}
        <div class="flex shrink-0 items-center justify-between mb-4 px-1">
            <div>
                <h1 class="text-xl font-bold text-gray-800">{{ $isDeveloperSupportMode ? 'Developers' : 'Messages' }}</h1>
                <p class="text-sm text-gray-500">
                    @if($isDeveloperSupportMode)
                        Staff Developer Support — submit reports and files; your tickets appear under <span class="font-medium text-gray-600">Developers → Tickets</span>
                    @else
                        Internal communications — {{ $roleLabel }}
                    @endif
                </p>
            </div>
            @if(!$isDeveloperSupportMode)
            <button id="btn-new-conversation"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700 transition-colors focus:outline-none">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Message
            </button>
            @endif
        </div>

        {{-- Main layout --}}
        @if($isDeveloperSupportMode)
            <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain p-5 bg-gray-50 rounded-2xl border border-gray-200">
                <div class="max-w-4xl mx-auto space-y-8 pb-8">
                    {{-- Report Form --}}
                    <section id="developer-report-form" class="scroll-mt-28 space-y-4">
                        <div class="border-b border-gray-200 pb-2">
                            <h2 class="text-base font-semibold text-gray-900">Report Form</h2>
                        </div>
                        <form id="support-report-form" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:items-start">
                                <div>
                                    <label for="support-type" class="block text-sm font-medium text-gray-700 mb-1">Report type</label>
                                    <select id="support-type" name="report_type" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                        @foreach(\App\Models\SupportReport::REPORT_TYPES as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="support-priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                    <select id="support-priority" name="priority" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                        @foreach(\App\Models\RepairTicket::PRIORITIES as $pr)
                                            <option value="{{ $pr }}" @selected($pr === \App\Models\RepairTicket::PRIORITY_MEDIUM)>{{ ucfirst($pr) }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">How urgently the development team should treat this request.</p>
                                </div>
                            </div>
                            <div>
                                <label for="support-subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <input id="support-subject" name="title" type="text" maxlength="150" placeholder="Short title of the concern" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                            </div>
                            <div>
                                <label for="support-details" class="block text-sm font-medium text-gray-700 mb-1">Details</label>
                                <textarea id="support-details" name="description" rows="5" maxlength="5000" placeholder="Describe what happened, expected output, and steps to reproduce." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none"></textarea>
                            </div>
                            <div>
                                <label for="support-files" class="block text-sm font-medium text-gray-700 mb-1">Attachments</label>
                                <input id="support-files" type="file" multiple class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-700" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,image/*">
                                <p class="mt-1 text-xs text-gray-500">Up to 5 files, 10MB each.</p>
                            </div>
                            <div id="support-error" class="hidden rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-600"></div>
                            <div class="flex justify-end">
                                <button type="submit" id="support-submit-btn" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Submit to Developers
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        @else
        <div class="flex flex-1 min-h-0 gap-4">

            {{-- ── LEFT: conversation list ── --}}
            <div class="w-80 flex-shrink-0 flex flex-col bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <input id="conv-search" type="text" placeholder="Search conversations…"
                           class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"/>
                </div>
                <ul id="conversation-list" class="flex-1 overflow-y-auto divide-y divide-gray-100">
                    @forelse($conversationsForDisplay as $conv)
                        @php
                            $other    = $conv->participants->first();
                            $lastMsg  = $conv->messages->first();
                            $unread   = $conv->unread_count ?? 0;
                            $isActive = $activeConv && $activeConv->id === $conv->id;
                        @endphp
                        <li class="conv-item" data-conv-id="{{ $conv->id }}" data-name="{{ strtolower($other->name ?? '') }}">
                            <a href="{{ route('messaging.index', array_filter(['conversation' => $conv->id, 'audience' => $isDeveloperSupportMode ? 'developers' : null])) }}"
                               class="flex items-center gap-3 px-4 py-3 hover:bg-indigo-50 transition-colors {{ $isActive ? 'bg-indigo-50 border-l-4 border-indigo-500' : '' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-600 flex-shrink-0 flex items-center justify-center text-white text-xs font-bold">
                                    {{ strtoupper(substr($other->name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-800 truncate">{{ $other->name ?? 'Unknown' }}</span>
                                        @if($unread > 0)
                                            <span class="ml-2 inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1 rounded-full bg-indigo-600 text-white text-xs font-bold">{{ $unread }}</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400 truncate">
                                        {{ $other->isSuperAdmin() ? 'Super Admin' : ($other->isAdmin() ? 'QA Coordinator' : ($other->isDeveloper() ? 'Developer' : 'Planning Coordinator')) }}
                                    </p>
                                    @if($lastMsg)
                                        @php
                                            $snip = trim((string) ($lastMsg->message ?? ''));
                                            if ($snip === '' && is_array($lastMsg->attachments ?? null) && count($lastMsg->attachments) > 0) {
                                                $snip = '📎 Attachment';
                                            }
                                        @endphp
                                        <p class="text-xs text-gray-500 truncate mt-0.5">
                                            {{ $lastMsg->sender_id === $authUser->id ? 'You: ' : '' }}{{ $snip !== '' ? Str::limit($snip, 38) : '🗑 Message deleted' }}
                                        </p>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-gray-400">
                            {{ $isDeveloperSupportMode ? 'No developer support threads yet.' : 'No conversations yet.' }}<br>
                            <span class="text-indigo-500 cursor-pointer" id="link-new-conv">{{ $isDeveloperSupportMode ? 'Create your first support report →' : 'Start one now →' }}</span>
                        </li>
                    @endforelse
                </ul>
            </div>

            {{-- ── RIGHT: chat pane ── --}}
            <div class="flex-1 flex flex-col bg-white rounded-2xl shadow border border-gray-200 overflow-hidden min-w-0">

                @if($activeConv && $otherUser)

                    {{-- Chat header --}}
                    <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-100 bg-white">
                        <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-bold">
                            {{ strtoupper(substr($otherUser->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800">{{ $otherUser->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $otherUser->isSuperAdmin() ? 'Super Admin' : ($otherUser->isAdmin() ? 'QA Coordinator' : ($otherUser->isDeveloper() ? 'Developer' : 'Planning Coordinator')) }}
                                @if($otherUser->campus) · {{ $otherUser->campus }}@endif
                            </p>
                        </div>
                        <div class="relative">
                            <button id="conv-menu-btn"
                                    class="h-9 w-9 rounded-full border border-gray-200 bg-white text-gray-500 hover:text-indigo-600 hover:border-indigo-300 transition-all flex items-center justify-center"
                                    title="Conversation options"
                                    aria-label="Conversation options">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/>
                                </svg>
                            </button>
                            <div id="conv-menu"
                                 class="hidden absolute right-0 top-11 z-30 min-w-[190px] rounded-xl border border-gray-200 bg-white py-1 shadow-xl">
                                <button id="conv-delete-btn"
                                        class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    Delete History
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Pinned messages banner --}}
                    @if($pinnedMessages->count())
                    <div id="pinned-banner" class="border-b border-yellow-200 bg-yellow-50 px-5 py-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="h-4 w-4 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/>
                                </svg>
                                <span class="text-xs font-semibold text-yellow-700 mr-1">Pinned:</span>
                                <span id="pinned-text" class="text-xs text-yellow-700 truncate">
                                    {{ Str::limit($pinnedMessages->last()->message ?? '', 60) }}
                                </span>
                            </div>
                            <button id="btn-show-pinned" class="ml-3 text-xs text-indigo-600 hover:underline flex-shrink-0">
                                {{ $pinnedMessages->count() > 1 ? 'View all ('.$pinnedMessages->count().')' : 'View' }}
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Messages container --}}
                    <div id="messages-container" class="flex-1 overflow-y-auto px-5 py-4 space-y-3 bg-gray-50">
                        @forelse($messages as $msg)
                            @php $isMine = $msg->sender_id === $authUser->id; @endphp
                            @php
                                $replyPreviewText = trim((string) ($msg->message ?? ''));
                                if ($replyPreviewText === '' && is_array($msg->attachments ?? null) && count($msg->attachments) > 0) {
                                    $replyPreviewText = '📎 Attachment';
                                }
                            @endphp
                            <div class="msg-wrap flex {{ $isMine ? 'justify-end' : 'justify-start' }} group"
                                 data-msg-id="{{ $msg->id }}"
                                 data-mine="{{ $isMine ? '1' : '0' }}"
                                 data-text="{{ $msg->deleted_at ? '' : e($replyPreviewText) }}"
                                 data-pinned="{{ $msg->is_pinned ? '1' : '0' }}">

                                <div class="max-w-[min(92vw,32rem)]">

                                    {{-- Reply context preview --}}
                                    @if($msg->replyTo)
                                    <div class="mb-1 rounded-lg border-l-4 border-indigo-400 bg-indigo-50 px-3 py-1.5 text-xs text-gray-600 cursor-pointer reply-quote"
                                         data-target="{{ $msg->replyTo->id }}">
                                        <span class="font-semibold text-indigo-600">{{ $msg->replyTo->sender->name ?? 'Unknown' }}</span>
                                        <p class="truncate mt-0.5 text-gray-500">
                                            {{ $msg->replyTo->deleted_at ? '🗑 Message deleted' : Str::limit($msg->replyTo->message, 60) }}
                                        </p>
                                    </div>
                                    @endif

                                    @if(!$isMine)
                                        <p class="text-xs text-gray-400 mb-1 ml-1">{{ $msg->sender->name }}</p>
                                    @endif

                                    {{-- Bubble + 3-dot side by side, vertically centered --}}
                                    {{-- flex-row-reverse: for my msgs the dot sits LEFT of bubble;
                                         for others it sits RIGHT of bubble — exactly like Messenger --}}
                                    <div class="flex items-center gap-1.5 {{ $isMine ? 'flex-row-reverse' : '' }}">

                                        {{-- Bubble --}}
                                        @php
                                            $hasMsgText = trim((string) $msg->message) !== '';
                                            $hasFiles = is_array($msg->attachments ?? null) && count($msg->attachments) > 0;
                                        @endphp
                                        <div class="msg-bubble rounded-2xl text-sm shadow-sm
                                            {{ $msg->deleted_at ? 'px-4 py-2 bg-gray-200 text-gray-400 italic' :
                                               ($isMine ? 'bg-indigo-600 text-white rounded-br-sm px-3 py-2.5 sm:px-4' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-sm px-3 py-2.5 sm:px-4') }}">
                                            @if($msg->deleted_at)
                                                Message deleted
                                            @else
                                                @if($msg->is_pinned)
                                                    <span class="inline-block mr-1" title="Pinned">
                                                        <svg class="inline h-3 w-3 text-yellow-400" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                                    </span>
                                                @endif
                                                @if($hasMsgText)
                                                    <div class="message-text leading-relaxed">{!! nl2br(e($msg->message)) !!}</div>
                                                @endif
                                                @if($msg->edited_at)
                                                    <span class="text-xs opacity-60 {{ $hasMsgText ? 'ml-1' : '' }}">(edited)</span>
                                                @endif
                                                @if($hasFiles)
                                                    <div class="msg-attachments space-y-2.5 {{ $hasMsgText || $msg->edited_at ? 'mt-3' : '' }}">
                                                        @foreach($msg->attachments as $idx => $att)
                                                            @php
                                                                $p = $att['path'] ?? '';
                                                                $disk = \Illuminate\Support\Facades\Storage::disk('public');
                                                                $mime = $att['mime'] ?? '';
                                                                $attUrl = ($p && $disk->exists($p))
                                                                    ? route('messaging.messages.attachment', ['message' => $msg->id, 'index' => $idx], false)
                                                                    : null;
                                                                $sz = (int) ($att['size'] ?? 0);
                                                                if ($sz <= 0 && $p && $disk->exists($p)) {
                                                                    try { $sz = (int) $disk->size($p); } catch (\Throwable $e) { $sz = 0; }
                                                                }
                                                                $sizeLabel = '';
                                                                if ($sz > 0) {
                                                                    if ($sz < 1024) {
                                                                        $sizeLabel = $sz.' B';
                                                                    } elseif ($sz < 1048576) {
                                                                        $sizeLabel = number_format($sz / 1024, 2).' KB';
                                                                    } else {
                                                                        $sizeLabel = number_format($sz / 1048576, 2).' MB';
                                                                    }
                                                                }
                                                            @endphp
                                                            @if($attUrl)
                                                                @if(str_starts_with((string) $mime, 'image/'))
                                                                    <div class="relative rounded-2xl overflow-hidden max-w-full {{ $isMine ? 'ring-2 ring-white/30 shadow-lg' : 'ring-1 ring-gray-200/80 shadow-md' }}">
                                                                        <button type="button"
                                                                                class="msg-lightbox-trigger group relative block w-full border-0 bg-black/5 p-0 text-left cursor-zoom-in"
                                                                                data-lightbox-src="{{ $attUrl }}"
                                                                                data-lightbox-name="{{ e($att['name'] ?? 'image') }}"
                                                                                title="View image"
                                                                                aria-label="View image full screen">
                                                                            <img src="{{ $attUrl }}" alt="" class="mess-inline-img w-full h-auto max-h-80 object-cover object-center block pointer-events-none" loading="lazy"/>
                                                                            <span class="pointer-events-none absolute bottom-2 right-2 rounded-md bg-black/55 px-2 py-0.5 text-[11px] font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">View</span>
                                                                        </button>
                                                                    </div>
                                                                @else
                                                                    <a href="{{ $attUrl }}" target="_blank" rel="noopener noreferrer"
                                                                       class="mess-file-card flex items-start gap-3 w-full max-w-sm rounded-xl px-3 py-2.5 text-left no-underline transition-colors
                                                                       {{ $isMine ? 'bg-indigo-900/80 hover:bg-indigo-900 ring-1 ring-white/20 text-white' : 'bg-slate-100 hover:bg-slate-200/90 ring-1 ring-slate-200 text-slate-800' }}">
                                                                        <svg class="h-10 w-10 flex-shrink-0 {{ $isMine ? 'text-white/95' : 'text-indigo-600' }}" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                                                                        </svg>
                                                                        <div class="min-w-0 flex-1 pt-0.5">
                                                                            <p class="text-sm font-semibold leading-snug break-words {{ $isMine ? 'text-white' : 'text-slate-900' }}">{{ $att['name'] ?? 'File' }}</p>
                                                                            @if($sizeLabel !== '')
                                                                                <p class="text-xs mt-1 {{ $isMine ? 'text-white/55' : 'text-slate-500' }}">{{ $sizeLabel }}</p>
                                                                            @endif
                                                                        </div>
                                                                    </a>
                                                                @endif
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif
                                        </div>

                                        {{-- 3-dot menu button --}}
                                        @if(!$msg->deleted_at)
                                        <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity msg-menu-wrap">
                                            <button class="msg-menu-btn h-7 w-7 rounded-full bg-white border border-gray-200 shadow-sm flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:border-indigo-300 hover:shadow transition-all"
                                                    data-msg-id="{{ $msg->id }}"
                                                    data-mine="{{ $isMine ? '1' : '0' }}"
                                                    data-pinned="{{ $msg->is_pinned ? '1' : '0' }}"
                                                    title="Message options">
                                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="5"  r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/>
                                                </svg>
                                            </button>
                                        </div>
                                        @endif

                                    </div>

                                    <p class="text-xs text-gray-400 mt-1 {{ $isMine ? 'text-right' : 'text-left' }}">
                                        {{ ($msg->created_at ?? now())->format('M j, g:i A') }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="flex items-center justify-center h-full">
                                <p class="text-sm text-gray-400">No messages yet. Say hello!</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Reply bar (hidden by default) --}}
                    <div id="reply-bar" class="hidden px-5 py-2 bg-indigo-50 border-t border-indigo-100 flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-indigo-600" id="reply-sender-name"></p>
                            <p class="text-xs text-gray-500 truncate" id="reply-preview"></p>
                        </div>
                        <button id="btn-cancel-reply" class="text-gray-400 hover:text-gray-600" title="Cancel reply">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Message input --}}
                    <div class="px-4 py-3 border-t border-gray-100 bg-white">
                        <div id="attachment-chips" class="hidden mb-2 flex flex-wrap gap-2"></div>
                        <form id="send-form"
                              data-url="{{ route('messaging.conversations.messages.send', $activeConv->id) }}"
                              class="flex items-end gap-2"
                              enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" id="reply-to-id" name="reply_to_id" value="">
                            <input type="file" id="msg-file-input" class="hidden" multiple
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,image/*">
                            <button type="button" id="btn-attach-image" title="Attach images"
                                    class="flex-shrink-0 inline-flex items-center justify-center h-10 w-10 rounded-xl border border-gray-200 bg-gray-50 text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </button>
                            <button type="button" id="btn-attach-file" title="Attach files"
                                    class="flex-shrink-0 inline-flex items-center justify-center h-10 w-10 rounded-xl border border-gray-200 bg-gray-50 text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                            </button>
                            <textarea id="msg-input" name="message" rows="1" placeholder="Type a message…" maxlength="5000"
                                      class="flex-1 resize-none rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all"
                                      style="max-height:62px;overflow-y:hidden;"></textarea>
                            <button type="submit" id="send-btn"
                                    class="flex-shrink-0 inline-flex items-center justify-center h-10 w-10 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-colors focus:outline-none disabled:opacity-50"
                                    title="Send">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </form>
                        <p class="mt-1.5 text-[11px] text-gray-400 px-1">Up to 5 files · 10 MB each · images, PDF, Office, zip</p>
                    </div>

                @else
                    <div class="flex flex-1 flex-col items-center justify-center text-center p-10">
                        <div class="h-20 w-20 rounded-full bg-indigo-50 flex items-center justify-center mb-4">
                            <svg class="h-10 w-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-700 mb-1">Select a conversation</h3>
                        <p class="text-sm text-gray-400 mb-4">Choose from the list on the left, or start a new message.</p>
                        <button id="btn-new-conversation-2"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700 transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            New Message
                        </button>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- ── 3-dot context menu (portal) ── --}}
    <div id="msg-context-menu"
         class="fixed z-[10050] hidden bg-white rounded-xl shadow-xl border border-gray-200 py-1 min-w-[160px]"
         role="menu">
        <button class="menu-action w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors" data-action="reply">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
            Reply
        </button>
        <button class="menu-action w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors" data-action="forward">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Forward
        </button>
        <button class="menu-action w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors" data-action="pin">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
            <span class="pin-label">Pin</span>
        </button>
        <div class="own-only">
            <div class="border-t border-gray-100 my-1"></div>
            <button class="menu-action w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors" data-action="edit">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </button>
            <button class="menu-action w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors" data-action="delete">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Delete
            </button>
        </div>
    </div>

    {{-- Image viewer: one top bar (labeled actions) + framed photo + thumbs — easier to understand than scattered icons --}}
    <div id="img-lightbox" class="fixed inset-0 z-[10060] hidden flex flex-col bg-black" aria-modal="true" role="dialog" aria-labelledby="img-lightbox-heading">
        {{-- Messenger-style: solid black behind everything (no gray scrim / blur) --}}
        <div class="absolute inset-0 z-0 bg-black" id="img-lightbox-backdrop" title="Close preview"></div>

        {{-- Top: close, title, Download --}}
        <header class="relative z-20 flex flex-shrink-0 items-center gap-3 bg-black px-3 py-3 sm:px-4">
            <button type="button" id="img-lightbox-close"
                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/20 transition-colors"
                    title="Close" aria-label="Close preview">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
            <h2 id="img-lightbox-heading" class="min-w-0 flex-1 truncate text-sm font-semibold text-white sm:text-base">
                Image preview
            </h2>
            <button type="button" id="img-lightbox-download"
                    class="inline-flex flex-shrink-0 items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-2 text-xs font-medium text-white ring-1 ring-white/20 hover:bg-white/20 sm:gap-2 sm:px-3 sm:text-sm"
                    title="Download this file" aria-label="Download">
                <svg class="h-4 w-4 flex-shrink-0 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12M12 16.5V3"/>
                </svg>
                <span>Download</span>
            </button>
        </header>

        {{-- Center: dimmed area closes on click; photo sits in a clear frame --}}
        <div class="relative z-10 flex min-h-0 flex-1 items-center justify-center px-3 py-3 pointer-events-none sm:px-6">
            <button type="button" id="img-lightbox-prev"
                    class="pointer-events-auto absolute left-1 top-1/2 z-30 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-white/25 shadow-lg hover:bg-white/25 disabled:pointer-events-none disabled:opacity-20 sm:left-3 md:left-6"
                    title="Previous image" aria-label="Previous image">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m14 18-6-6 6-6"/></svg>
            </button>
            <button type="button" id="img-lightbox-next"
                    class="pointer-events-auto absolute right-1 top-1/2 z-30 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-white/25 shadow-lg hover:bg-white/25 disabled:pointer-events-none disabled:opacity-20 sm:right-3 md:right-6"
                    title="Next image" aria-label="Next image">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m10 18 6-6-6-6"/></svg>
            </button>

            <div class="pointer-events-auto flex max-h-full max-w-full flex-col items-center">
                <div class="rounded-lg sm:rounded-xl">
                    <img id="img-lightbox-main" src="" alt=""
                         class="max-h-[min(72vh,calc(100vh-10rem))] max-w-[min(100vw-2rem,56rem)] w-auto object-contain rounded-lg sm:rounded-xl select-none shadow-none"/>
                </div>
                <p id="img-lightbox-hint" class="mt-3 max-w-md text-center text-[11px] leading-snug text-white/45 sm:text-xs">
                    Tap outside the photo or <kbd class="rounded bg-white/10 px-1 py-0.5 font-sans text-[10px] text-white/70">Esc</kbd> to close.
                </p>
            </div>
        </div>

        <footer class="relative z-20 flex-shrink-0 bg-black px-3 py-3">
            <p id="img-lightbox-gallery-hint" class="mb-2 hidden text-center text-[11px] text-white/45">More images in this chat — use arrows, thumbnails, or keyboard.</p>
            <div id="img-lightbox-thumbs" class="mx-auto flex max-w-full justify-center gap-2 overflow-x-auto py-1 scrollbar-thin"></div>
        </footer>
    </div>

    {{-- ── Delete history modal ── --}}
    <div id="modal-delete-conv" class="fixed inset-0 z-[10002] hidden" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" id="delete-conv-backdrop"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-5 relative z-10">
                <h2 class="text-2xl font-semibold text-gray-900 mb-2">Delete History</h2>
                <p class="text-base text-gray-800 leading-relaxed mb-4">
                    Are you sure you want to clear your chat history with <strong>{{ $otherUser->name ?? 'this user' }}</strong>?
                </p>

                <label class="flex items-start gap-3 mb-6 cursor-pointer select-none">
                    <input id="delete-for-everyone" type="checkbox"
                           class="mt-0.5 h-5 w-5 rounded border-2 border-gray-400 text-indigo-600 focus:ring-2 focus:ring-indigo-300" />
                    <span class="text-sm text-gray-900 leading-snug">Also clear history for {{ $otherUser->name ?? 'this user' }}</span>
                </label>

                <div id="delete-conv-error" class="hidden mb-4 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-600"></div>

                <div class="flex justify-end items-center gap-6">
                    <button id="delete-conv-cancel" type="button"
                            class="text-2xl leading-none text-sky-500 hover:text-sky-600 transition-colors">
                        Cancel
                    </button>
                    <button id="delete-conv-confirm" type="button"
                            class="text-2xl leading-none text-red-500 hover:text-red-600 transition-colors">
                        Delete History
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── New conversation modal ── --}}
    <div id="modal-new-conv" class="fixed inset-0 z-[10002] hidden" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" id="modal-backdrop"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-800">{{ $isDeveloperSupportMode ? 'Contact Developers' : 'New Message' }}</h2>
                    <button id="modal-close" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-new-conv" action="{{ route('messaging.conversations.start') }}" method="POST">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $isDeveloperSupportMode ? 'Send support concern to' : 'Send message to' }}</label>
                    <input type="hidden" name="recipient_id" id="recipient-select" value="">
                    <div class="mb-4">
                        <div class="relative mb-2">
                            <input id="recipient-search" type="text" placeholder="{{ $isDeveloperSupportMode ? 'Search developer...' : 'Search user or campus...' }}"
                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/>
                            </svg>
                        </div>
                        <div id="recipient-list" class="max-h-64 overflow-y-auto rounded-xl border border-gray-200 divide-y divide-gray-100 bg-white">
                            @foreach($selectableUsers as $u)
                                @php $uRole = $u->isSuperAdmin() ? 'Super Admin' : ($u->isAdmin() ? 'QA Coordinator' : ($u->isDeveloper() ? 'Developer' : 'Planning Coordinator')); @endphp
                                @php $detail = $uRole . ($u->campus ? ', '.$u->campus : ''); @endphp
                                <button type="button"
                                        class="recipient-option w-full px-3 py-2.5 text-left hover:bg-indigo-50 transition-colors"
                                        data-user-id="{{ $u->id }}"
                                        data-search="{{ strtolower($u->name . ' ' . $uRole . ' ' . ($u->campus ?? '')) }}">
                                    <p class="text-sm font-medium text-gray-800">{{ $u->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $detail }}</p>
                                </button>
                            @endforeach
                        </div>
                        <p id="recipient-selected-label" class="mt-2 text-xs text-gray-500">No user selected.</p>
                    </div>
                    <div id="modal-error" class="hidden mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-600"></div>
                    <div class="flex gap-3 justify-end">
                        <button type="button" id="modal-cancel" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ $isDeveloperSupportMode ? 'Start Support Chat' : 'Start Chat' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Edit modal ── --}}
    <div id="modal-edit" class="fixed inset-0 z-[10002] hidden" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-800">Edit Message</h2>
                    <button id="edit-modal-close" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <textarea id="edit-input" rows="4" maxlength="5000"
                          class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 mb-4 resize-none"></textarea>
                <div id="edit-error" class="hidden mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-600"></div>
                <div class="flex gap-3 justify-end">
                    <button id="edit-cancel" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button id="edit-save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Forward modal ── --}}
    <div id="modal-forward" class="fixed inset-0 z-[10002] hidden" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-800">Forward Message</h2>
                    <button id="forward-modal-close" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div id="forward-preview" class="mb-4 rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-600 italic truncate"></div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Forward to</label>
                <select id="forward-conv-select"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 mb-4">
                    <option value="">— Select a conversation —</option>
                    @foreach($conversations as $conv)
                        @if(!$activeConv || $conv->id !== $activeConv->id)
                            @php $fOther = $conv->participants->first(); @endphp
                            <option value="{{ $conv->id }}">{{ $fOther->name ?? 'Unknown' }}</option>
                        @endif
                    @endforeach
                </select>
                <div id="forward-error" class="hidden mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-600"></div>
                <div class="flex gap-3 justify-end">
                    <button id="forward-cancel" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button id="forward-send" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Forward</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Pinned messages modal ── --}}
    <div id="modal-pinned" class="fixed inset-0 z-[10002] hidden" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-800">📌 Pinned Messages</h2>
                    <button id="pinned-modal-close" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <ul id="pinned-list" class="space-y-3 max-h-80 overflow-y-auto">
                    @foreach($pinnedMessages as $pm)
                    <li class="rounded-xl border border-gray-200 px-4 py-3 text-sm" data-msg-id="{{ $pm->id }}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-gray-700">{{ $pm->sender->name ?? 'Unknown' }}</span>
                            <button class="unpin-btn text-xs text-red-500 hover:text-red-700" data-msg-id="{{ $pm->id }}">Unpin</button>
                        </div>
                        <p class="text-gray-600 text-xs">{{ Str::limit($pm->message, 120) }}</p>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <script>
    (function () {
        document.body.classList.add('messaging-page');
        window.addEventListener('beforeunload', () => document.body.classList.remove('messaging-page', 'img-lightbox-open'));

        const authUserId = {{ $authUser->id }};
        const csrf       = document.querySelector('meta[name="csrf-token"]').content;
        const isDeveloperSupportMode = @json($isDeveloperSupportMode);
        const supportDefaultRecipientId = @json($supportDefaultRecipientId);
        const supportQuerySuffix = isDeveloperSupportMode ? '&audience=developers' : '';

        // Match chat panel bottom to sidebar bottom (16px viewport margin)
        function syncMessagingHeight() {
            const shell = document.getElementById('messaging-shell');
            if (!shell) return;
            const top = shell.getBoundingClientRect().top;
            const bottomGap = 16; // same visual margin used by sidebar (top-4 / left-4 style spacing)
            const computed = Math.max(window.innerHeight - top - bottomGap, 360);
            shell.style.height = `${computed}px`;
        }
        if (!isDeveloperSupportMode) {
            syncMessagingHeight();
            window.addEventListener('resize', syncMessagingHeight);
        } else {
            document.body.classList.add('developer-support-fixed');
        }

        // ── helpers ──────────────────────────────────────────────────────────
        function esc(str) {
            return String(str ?? '')
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        }
        function fmtDate(iso) {
            const d = new Date(iso);
            return d.toLocaleString('en-US', { month:'short', day:'numeric', hour:'numeric', minute:'2-digit' });
        }
        async function apiCall(method, url, body = null) {
            const opts = {
                method,
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            };
            if (body) {
                opts.headers['Content-Type'] = 'application/json';
                opts.body = JSON.stringify(body);
            }
            return fetch(url, opts).then(r => r.json().then(d => ({ ok: r.ok, data: d })));
        }

        // Developer support page: report form only (tickets live on Tickets page).
        if (isDeveloperSupportMode) {
            const supportForm = document.getElementById('support-report-form');
            if (!supportForm) return;

            supportForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const errEl = document.getElementById('support-error');
                const submitBtn = document.getElementById('support-submit-btn');
                errEl?.classList.add('hidden');

                const type = (document.getElementById('support-type')?.value || '').trim();
                const priority = (document.getElementById('support-priority')?.value || '').trim();
                const subject = (document.getElementById('support-subject')?.value || '').trim();
                const details = (document.getElementById('support-details')?.value || '').trim();
                const filesInput = document.getElementById('support-files');
                const files = Array.from(filesInput?.files || []).slice(0, 5);

                if (!subject || !details || !priority) {
                    errEl.textContent = 'Report type, priority, subject, and details are required.';
                    errEl.classList.remove('hidden');
                    return;
                }

                submitBtn.disabled = true;
                try {
                    const payload = new FormData();
                    payload.append('_token', csrf);
                    payload.append('report_type', type);
                    payload.append('priority', priority);
                    payload.append('title', subject);
                    payload.append('description', details);
                    files.forEach(file => payload.append('attachments[]', file));

                    const res = await fetch('{{ route("messaging.support-reports.store") }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: payload,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const msg = data.message || data.error
                            || (typeof data.errors === 'object' && data.errors ? Object.values(data.errors).flat().join(' ') : null)
                            || 'Failed to submit report.';
                        throw new Error(msg);
                    }

                    supportForm.reset();
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }
                    alert('Report submitted. A repair ticket has been created.');
                } catch (error) {
                    errEl.textContent = error.message || 'Failed to submit concern.';
                    errEl.classList.remove('hidden');
                } finally {
                    submitBtn.disabled = false;
                }
            });

            return;
        }

        // ── modal helpers ─────────────────────────────────────────────────────
        function showModal(id) { document.getElementById(id)?.classList.remove('hidden'); }
        function hideModal(id) { document.getElementById(id)?.classList.add('hidden'); }

        // Conversation header 3-dot menu + delete modal
        const convMenuBtn = document.getElementById('conv-menu-btn');
        const convMenu = document.getElementById('conv-menu');
        const convDeleteBtn = document.getElementById('conv-delete-btn');
        const deleteConvCancel = document.getElementById('delete-conv-cancel');
        const deleteConvConfirm = document.getElementById('delete-conv-confirm');
        const deleteConvBackdrop = document.getElementById('delete-conv-backdrop');

        convMenuBtn?.addEventListener('click', function (e) {
            e.stopPropagation();
            convMenu?.classList.toggle('hidden');
        });
        convDeleteBtn?.addEventListener('click', function () {
            convMenu?.classList.add('hidden');
            document.getElementById('delete-conv-error')?.classList.add('hidden');
            showModal('modal-delete-conv');
        });
        deleteConvCancel?.addEventListener('click', () => hideModal('modal-delete-conv'));
        deleteConvBackdrop?.addEventListener('click', () => hideModal('modal-delete-conv'));
        document.addEventListener('click', function (e) {
            if (!convMenu || convMenu.classList.contains('hidden')) return;
            if (!e.target.closest('#conv-menu') && !e.target.closest('#conv-menu-btn')) {
                convMenu.classList.add('hidden');
            }
        });
        deleteConvConfirm?.addEventListener('click', async function () {
            const errEl = document.getElementById('delete-conv-error');
            errEl?.classList.add('hidden');
            const forEveryone = document.getElementById('delete-for-everyone')?.checked ? 1 : 0;
            const conversationId = @json($activeConv?->id);

            const { ok, data } = await apiCall('DELETE', `/messaging/conversations/${conversationId}`, {
                for_everyone: forEveryone,
            });

            if (!ok) {
                if (errEl) {
                    errEl.textContent = data.error || 'Failed to delete history.';
                    errEl.classList.remove('hidden');
                }
                return;
            }
            const activeConversationId = @json($activeConv?->id);
            window.location.href = '{{ route("messaging.index") }}?conversation=' + activeConversationId + supportQuerySuffix;
        });

        // New conversation modal
        function openNewConversationModal() {
            const errEl = document.getElementById('modal-error');
            if (errEl) errEl.classList.add('hidden');
            if (recipientSearch) recipientSearch.value = '';
            if (recipientHidden) recipientHidden.value = '';
            if (recipientLabel) recipientLabel.textContent = 'No user selected.';
            recipientOptions.forEach(el => {
                el.classList.remove('hidden', 'bg-indigo-50', 'ring-1', 'ring-indigo-200');
            });
            if (isDeveloperSupportMode && supportDefaultRecipientId) {
                const defaultBtn = recipientOptions.find(el => Number(el.dataset.userId) === Number(supportDefaultRecipientId));
                if (defaultBtn) setRecipientSelection(defaultBtn);
            }
            showModal('modal-new-conv');
        }

        document.getElementById('btn-new-conversation')?.addEventListener('click', openNewConversationModal);
        document.getElementById('btn-new-conversation-2')?.addEventListener('click', openNewConversationModal);
        document.getElementById('link-new-conv')?.addEventListener('click', openNewConversationModal);
        document.getElementById('modal-close')?.addEventListener('click',    () => hideModal('modal-new-conv'));
        document.getElementById('modal-cancel')?.addEventListener('click',   () => hideModal('modal-new-conv'));
        document.getElementById('modal-backdrop')?.addEventListener('click', () => hideModal('modal-new-conv'));

        const recipientSearch = document.getElementById('recipient-search');
        const recipientHidden = document.getElementById('recipient-select');
        const recipientLabel = document.getElementById('recipient-selected-label');
        const recipientOptions = Array.from(document.querySelectorAll('.recipient-option'));

        function setRecipientSelection(btn) {
            recipientOptions.forEach(el => el.classList.remove('bg-indigo-50', 'ring-1', 'ring-indigo-200'));
            btn.classList.add('bg-indigo-50', 'ring-1', 'ring-indigo-200');
            recipientHidden.value = btn.dataset.userId;
            const name = btn.querySelector('p:first-child')?.textContent?.trim() || 'User';
            recipientLabel.textContent = `Selected: ${name}`;
        }

        recipientOptions.forEach(btn => {
            btn.addEventListener('click', () => setRecipientSelection(btn));
        });

        recipientSearch?.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            recipientOptions.forEach(btn => {
                const ok = (btn.dataset.search || '').includes(q);
                btn.classList.toggle('hidden', !ok);
                if (ok) visible++;
            });
            recipientLabel.textContent = visible ? (recipientHidden.value ? recipientLabel.textContent : 'No user selected.') : 'No matching users found.';
        });

        document.getElementById('form-new-conv')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const errEl = document.getElementById('modal-error');
            errEl.classList.add('hidden');
            const recipientId = document.getElementById('recipient-select').value;
            if (!recipientId) { errEl.textContent = 'Please select a user.'; errEl.classList.remove('hidden'); return; }
            const { ok, data } = await apiCall('POST', this.action, { recipient_id: recipientId, _token: csrf });
            if (!ok) { errEl.textContent = data.error || 'An error occurred.'; errEl.classList.remove('hidden'); return; }
            hideModal('modal-new-conv');
            window.location.href = '{{ route("messaging.index") }}?conversation=' + data.conversation_id + supportQuerySuffix;
        });


        // Conversation search
        document.getElementById('conv-search')?.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.conv-item').forEach(li => {
                li.style.display = (li.dataset.name || '').includes(q) ? '' : 'none';
            });
        });

        // ── auto-resize textarea: grow to ~2 lines (text-sm + padding), then scroll (no bar on single line) ──
        const MSG_INPUT_MAX_PX = 62;
        const msgInput = document.getElementById('msg-input');
        function syncMsgInputSize() {
            if (!msgInput) return;
            msgInput.style.height = 'auto';
            const sh = msgInput.scrollHeight;
            msgInput.style.height = Math.min(sh, MSG_INPUT_MAX_PX) + 'px';
            msgInput.style.overflowY = sh > MSG_INPUT_MAX_PX ? 'auto' : 'hidden';
        }
        if (msgInput) {
            msgInput.addEventListener('input', syncMsgInputSize);
            msgInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    document.getElementById('send-form')?.dispatchEvent(new Event('submit'));
                }
            });
            syncMsgInputSize();
        }

        // ── pending file attachments (max 5) ──────────────────────────────────
        const fileInput = document.getElementById('msg-file-input');
        const attachmentChips = document.getElementById('attachment-chips');
        const pendingFiles = [];
        const MAX_ATTACH = 5;

        function renderAttachmentChips() {
            if (!attachmentChips) return;
            attachmentChips.innerHTML = '';
            if (pendingFiles.length === 0) {
                attachmentChips.classList.add('hidden');
                return;
            }
            attachmentChips.classList.remove('hidden');
            pendingFiles.forEach((file, idx) => {
                const chip = document.createElement('span');
                chip.className = 'inline-flex items-center gap-1 rounded-full bg-indigo-100 text-indigo-800 pl-3 pr-1 py-0.5 text-xs font-medium max-w-full';
                chip.innerHTML = '<span class="truncate max-w-[160px]">' + esc(file.name) + '</span>' +
                    '<button type="button" class="chip-remove ml-0.5 rounded-full px-1.5 hover:bg-indigo-200 leading-none" data-idx="' + idx + '" aria-label="Remove">×</button>';
                chip.querySelector('.chip-remove').addEventListener('click', function () {
                    const i = parseInt(this.getAttribute('data-idx'), 10);
                    if (!isNaN(i)) pendingFiles.splice(i, 1);
                    renderAttachmentChips();
                });
                attachmentChips.appendChild(chip);
            });
        }

        document.getElementById('btn-attach-image')?.addEventListener('click', () => {
            if (!fileInput) return;
            fileInput.accept = 'image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp';
            fileInput.click();
        });
        document.getElementById('btn-attach-file')?.addEventListener('click', () => {
            if (!fileInput) return;
            fileInput.accept = '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,application/pdf';
            fileInput.click();
        });
        fileInput?.addEventListener('change', function () {
            const room = Math.max(0, MAX_ATTACH - pendingFiles.length);
            Array.from(this.files || []).slice(0, room).forEach(f => {
                if (pendingFiles.length < MAX_ATTACH) pendingFiles.push(f);
            });
            this.value = '';
            renderAttachmentChips();
        });

        // ── reply bar ─────────────────────────────────────────────────────────
        let replyingToId   = null;
        const replyBar     = document.getElementById('reply-bar');
        const replyToIdEl  = document.getElementById('reply-to-id');

        function setReply(msgId, senderName, preview) {
            replyingToId = msgId;
            replyToIdEl.value = msgId;
            document.getElementById('reply-sender-name').textContent = senderName;
            document.getElementById('reply-preview').textContent     = preview;
            replyBar?.classList.remove('hidden');
            msgInput?.focus();
        }
        function clearReply() {
            replyingToId = null;
            replyToIdEl.value = '';
            replyBar?.classList.add('hidden');
        }
        document.getElementById('btn-cancel-reply')?.addEventListener('click', clearReply);

        // Click on quoted reply → scroll to original
        document.querySelectorAll('.reply-quote').forEach(el => {
            el.addEventListener('click', function() {
                const target = document.querySelector(`[data-msg-id="${this.dataset.target}"]`);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    target.classList.add('ring-2','ring-indigo-400','rounded-xl');
                    setTimeout(() => target.classList.remove('ring-2','ring-indigo-400','rounded-xl'), 1500);
                }
            });
        });

        // ── send message ──────────────────────────────────────────────────────
        const sendForm = document.getElementById('send-form');
        if (sendForm) {
            sendForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const text = msgInput.value.trim();
                if (!text && pendingFiles.length === 0) return;
                const sendBtn = document.getElementById('send-btn');
                sendBtn.disabled = true;

                let ok, data;
                try {
                    if (pendingFiles.length > 0) {
                        const fd = new FormData();
                        fd.append('_token', csrf);
                        fd.append('message', text);
                        pendingFiles.forEach(f => fd.append('attachments[]', f));
                        if (replyingToId) fd.append('reply_to_id', String(replyingToId));
                        const res = await fetch(this.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: fd,
                        });
                        data = await res.json().catch(() => ({}));
                        ok = res.ok;
                    } else {
                        const body = { message: text };
                        if (replyingToId) body.reply_to_id = replyingToId;
                        const r = await apiCall('POST', this.dataset.url, body);
                        ok = r.ok;
                        data = r.data;
                    }
                } catch (err) {
                    ok = false;
                    data = { error: 'Network error.' };
                }

                if (!ok) {
                    let err = (data && data.error) || (data && data.message) || 'Failed to send message.';
                    if (data && data.errors) {
                        const first = Object.values(data.errors)[0];
                        if (Array.isArray(first) && first[0]) err = first[0];
                    }
                    alert(err);
                    sendBtn.disabled = false;
                    return;
                }

                appendMessage(data.message);
                clearReply();
                pendingFiles.length = 0;
                renderAttachmentChips();
                msgInput.value = '';
                syncMsgInputSize();
                sendBtn.disabled = false;
                msgInput.focus();
            });
        }

        // ── 3-dot menu ────────────────────────────────────────────────────────
        const ctxMenu = document.getElementById('msg-context-menu');
        let activeMenuMsgId   = null;
        let activeMenuIsMine  = false;
        let activeMenuPinned  = false;

        function closeCtxMenu() {
            ctxMenu.classList.add('hidden');
            activeMenuMsgId  = null;
        }

        document.addEventListener('click', function(e) {
            if (!ctxMenu.contains(e.target) && !e.target.closest('.msg-menu-btn')) {
                closeCtxMenu();
            }
        });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.msg-menu-btn');
            if (!btn) return;
            e.stopPropagation();

            activeMenuMsgId  = parseInt(btn.dataset.msgId);
            activeMenuIsMine = btn.dataset.mine === '1';
            activeMenuPinned = btn.dataset.pinned === '1';

            // Show/hide own-only actions
            ctxMenu.querySelectorAll('.own-only').forEach(el => {
                el.style.display = activeMenuIsMine ? '' : 'none';
            });
            // Pin label
            ctxMenu.querySelector('.pin-label').textContent = activeMenuPinned ? 'Unpin' : 'Pin';

            // Position menu
            const rect = btn.getBoundingClientRect();
            ctxMenu.classList.remove('hidden');
            const menuH = ctxMenu.offsetHeight || 200;
            const spaceBelow = window.innerHeight - rect.bottom;
            ctxMenu.style.top  = spaceBelow > menuH ? (rect.bottom + 4 + window.scrollY) + 'px'
                                                      : (rect.top - menuH - 4 + window.scrollY) + 'px';
            ctxMenu.style.left = Math.min(rect.left, window.innerWidth - 180) + 'px';
        });

        ctxMenu.querySelectorAll('.menu-action').forEach(btn => {
            btn.addEventListener('click', async function() {
                const action = this.dataset.action;
                const msgId  = activeMenuMsgId;
                closeCtxMenu();

                if (action === 'reply')   doReply(msgId);
                if (action === 'forward') doForward(msgId);
                if (action === 'pin')     activeMenuPinned ? doUnpin(msgId) : doPin(msgId);
                if (action === 'edit')    doEdit(msgId);
                if (action === 'delete')  doDelete(msgId);
            });
        });

        // ── REPLY action ──────────────────────────────────────────────────────
        function doReply(msgId) {
            const wrap = document.querySelector(`[data-msg-id="${msgId}"]`);
            if (!wrap) return;
            const isMine    = wrap.dataset.mine === '1';
            const textEl    = wrap.querySelector('.msg-bubble');
            const preview   = wrap.dataset.text || 'Message';
            const name      = isMine ? 'You' : (wrap.querySelector('.text-xs.text-gray-400')?.textContent || 'User');
            setReply(msgId, name, preview);
        }

        // ── EDIT action ───────────────────────────────────────────────────────
        let editingMsgId = null;
        function doEdit(msgId) {
            const wrap = document.querySelector(`[data-msg-id="${msgId}"]`);
            if (!wrap) return;
            editingMsgId = msgId;
            document.getElementById('edit-input').value = wrap.dataset.text || '';
            document.getElementById('edit-error').classList.add('hidden');
            showModal('modal-edit');
        }
        document.getElementById('edit-modal-close')?.addEventListener('click', () => hideModal('modal-edit'));
        document.getElementById('edit-cancel')?.addEventListener('click',       () => hideModal('modal-edit'));
        document.getElementById('edit-save')?.addEventListener('click', async function() {
            const newText = document.getElementById('edit-input').value.trim();
            if (!newText) return;
            const errEl = document.getElementById('edit-error');
            errEl.classList.add('hidden');
            const { ok, data } = await apiCall('PATCH', `/messaging/messages/${editingMsgId}`, { message: newText });
            if (!ok) { errEl.textContent = data.error || 'Failed to edit.'; errEl.classList.remove('hidden'); return; }
            updateMessageBubble(data.message);
            hideModal('modal-edit');
        });

        // ── DELETE action ─────────────────────────────────────────────────────
        async function doDelete(msgId) {
            if (!confirm('Delete this message? This cannot be undone.')) return;
            const { ok, data } = await apiCall('DELETE', `/messaging/messages/${msgId}`);
            if (!ok) { alert(data.error || 'Failed to delete.'); return; }
            markMessageDeleted(msgId);
        }

        // ── FORWARD action ────────────────────────────────────────────────────
        let forwardingMsgId = null;
        function doForward(msgId) {
            const wrap = document.querySelector(`[data-msg-id="${msgId}"]`);
            forwardingMsgId = msgId;
            document.getElementById('forward-preview').textContent = wrap?.dataset.text || 'Message';
            document.getElementById('forward-error').classList.add('hidden');
            showModal('modal-forward');
        }
        document.getElementById('forward-modal-close')?.addEventListener('click', () => hideModal('modal-forward'));
        document.getElementById('forward-cancel')?.addEventListener('click',       () => hideModal('modal-forward'));
        document.getElementById('forward-send')?.addEventListener('click', async function() {
            const convId = document.getElementById('forward-conv-select').value;
            const errEl  = document.getElementById('forward-error');
            errEl.classList.add('hidden');
            if (!convId) { errEl.textContent = 'Please select a conversation.'; errEl.classList.remove('hidden'); return; }
            const { ok, data } = await apiCall('POST', `/messaging/messages/${forwardingMsgId}/forward`, { conversation_id: convId });
            if (!ok) { errEl.textContent = data.error || 'Failed to forward.'; errEl.classList.remove('hidden'); return; }
            hideModal('modal-forward');
            // Navigate to the target conversation
            window.location.href = '{{ route("messaging.index") }}?conversation=' + convId;
        });

        // ── PIN / UNPIN ───────────────────────────────────────────────────────
        async function doPin(msgId) {
            const { ok, data } = await apiCall('POST', `/messaging/messages/${msgId}/pin`);
            if (!ok) { alert(data.error || 'Failed to pin.'); return; }
            updateMessageBubble(data.message);
            refreshPinnedBanner(data.message, true);
        }
        async function doUnpin(msgId) {
            const { ok, data } = await apiCall('DELETE', `/messaging/messages/${msgId}/pin`);
            if (!ok) { alert(data.error || 'Failed to unpin.'); return; }
            updatePinnedStatus(msgId, false);
            removePinnedItem(msgId);
        }

        // Unpin from pinned modal
        document.querySelectorAll('.unpin-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const mid = parseInt(this.dataset.msgId);
                await doUnpin(mid);
            });
        });
        document.getElementById('btn-show-pinned')?.addEventListener('click',  () => showModal('modal-pinned'));
        document.getElementById('pinned-modal-close')?.addEventListener('click', () => hideModal('modal-pinned'));

        // ── DOM update helpers ────────────────────────────────────────────────
        function formatFileSize(bytes) {
            const b = parseInt(String(bytes), 10);
            if (!b || isNaN(b) || b <= 0) return '';
            if (b < 1024) return b + ' B';
            if (b < 1048576) return (b / 1024).toFixed(2) + ' KB';
            return (b / 1048576).toFixed(2) + ' MB';
        }

        function attachmentsHtml(msg, isMine) {
            if (!msg.attachments || !msg.attachments.length) return '';
            const needTop = ((msg.message || '').trim() !== '') || !!msg.edited_at;
            const docIconClass = isMine ? 'text-white/95' : 'text-indigo-600';
            const docIcon = '<svg class="h-10 w-10 flex-shrink-0 ' + docIconClass + '" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>';
            let html = '<div class="msg-attachments space-y-2.5' + (needTop ? ' mt-3' : '') + '">';
            msg.attachments.forEach(a => {
                if (a.is_image) {
                    const ring = isMine ? 'ring-2 ring-white/30 shadow-lg' : 'ring-1 ring-gray-200/80 shadow-md';
                    html += '<div class="relative rounded-2xl overflow-hidden max-w-full ' + ring + '">' +
                        '<button type="button" class="msg-lightbox-trigger group relative block w-full border-0 bg-black/5 p-0 text-left cursor-zoom-in" data-lightbox-src="' + esc(a.url) + '" data-lightbox-name="' + esc(a.name || 'image') + '" title="View image" aria-label="View image full screen">' +
                        '<img src="' + esc(a.url) + '" alt="" class="mess-inline-img w-full h-auto max-h-80 object-cover object-center block pointer-events-none" loading="lazy"/>' +
                        '<span class="pointer-events-none absolute bottom-2 right-2 rounded-md bg-black/55 px-2 py-0.5 text-[11px] font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">View</span></button></div>';
                } else {
                    const cardCls = isMine
                        ? 'bg-indigo-900/80 hover:bg-indigo-900 ring-1 ring-white/20 text-white'
                        : 'bg-slate-100 hover:bg-slate-200/90 ring-1 ring-slate-200 text-slate-800';
                    const titleCls = isMine ? 'text-white' : 'text-slate-900';
                    const subCls = isMine ? 'text-white/55' : 'text-slate-500';
                    const szLabel = (a.size_label && String(a.size_label)) || formatFileSize(a.size);
                    const sub = szLabel ? '<p class="text-xs mt-1 ' + subCls + '">' + esc(szLabel) + '</p>' : '';
                    html += '<a href="' + esc(a.url) + '" target="_blank" rel="noopener noreferrer" class="mess-file-card flex items-start gap-3 w-full max-w-sm rounded-xl px-3 py-2.5 text-left no-underline transition-colors ' + cardCls + '">' +
                        docIcon +
                        '<div class="min-w-0 flex-1 pt-0.5"><p class="text-sm font-semibold leading-snug break-words ' + titleCls + '">' + esc(a.name) + '</p>' + sub + '</div></a>';
                }
            });
            html += '</div>';
            return html;
        }

        function previewTextFromMsg(msg) {
            const t = (msg.message || '').trim();
            if (t) return t;
            if (msg.attachments && msg.attachments.length) return '📎 Attachment';
            return '';
        }

        function appendMessage(msg) {
            const container = document.getElementById('messages-container');
            if (!container) return;

            // Remove "no messages yet" placeholder
            const empty = container.querySelector('.text-gray-400');
            if (empty?.textContent.includes('No messages')) empty.closest('div')?.remove();

            const isMine = msg.sender_id === authUserId;
            const replyHtml = msg.reply_to ? `
                <div class="mb-1 rounded-lg border-l-4 border-indigo-400 bg-indigo-50 px-3 py-1.5 text-xs text-gray-600">
                    <span class="font-semibold text-indigo-600">${esc(msg.reply_to.sender_name)}</span>
                    <p class="truncate mt-0.5 text-gray-500">${msg.reply_to.deleted ? '🗑 Message deleted' : esc(msg.reply_to.message)}</p>
                </div>` : '';

            const bodyText = msg.message ? '<div class="message-text leading-relaxed">' + esc(msg.message).replace(/\n/g,'<br>') + '</div>' : '';
            const editedInline = msg.edited_at ? '<span class="text-xs opacity-60 ' + (msg.message ? 'ml-1' : '') + '">(edited)</span>' : '';
            const attHtml = attachmentsHtml(msg, isMine);

            const w = document.createElement('div');
            w.className = `msg-wrap flex ${isMine ? 'justify-end' : 'justify-start'} group`;
            w.dataset.msgId  = msg.id;
            w.dataset.mine   = isMine ? '1' : '0';
            w.dataset.text   = previewTextFromMsg(msg);
            w.dataset.pinned = '0';
            w.innerHTML = `
                <div class="max-w-[min(92vw,32rem)]">
                    ${replyHtml}
                    ${!isMine ? `<p class="text-xs text-gray-400 mb-1 ml-1">${esc(msg.sender?.name || '')}</p>` : ''}
                    <div class="flex items-center gap-1.5 ${isMine ? 'flex-row-reverse' : ''}">
                        <div class="msg-bubble rounded-2xl text-sm shadow-sm ${isMine ? 'bg-indigo-600 text-white rounded-br-sm px-3 py-2.5 sm:px-4' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-sm px-3 py-2.5 sm:px-4'}">
                            ${bodyText}
                            ${editedInline}
                            ${attHtml}
                        </div>
                        <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity msg-menu-wrap">
                            <button class="msg-menu-btn h-7 w-7 rounded-full bg-white border border-gray-200 shadow-sm flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:border-indigo-300 hover:shadow transition-all"
                                    data-msg-id="${msg.id}" data-mine="${isMine ? '1' : '0'}" data-pinned="0" title="Message options">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1 ${isMine ? 'text-right' : 'text-left'}">${fmtDate(msg.created_at)}</p>
                </div>`;

            container.appendChild(w);
            container.scrollTop = container.scrollHeight;
        }

        function updateMessageBubble(msg) {
            const wrap = document.querySelector(`[data-msg-id="${msg.id}"]`);
            if (!wrap) return;
            wrap.dataset.text   = previewTextFromMsg(msg);
            wrap.dataset.pinned = msg.is_pinned ? '1' : '0';
            const bubble = wrap.querySelector('.msg-bubble');
            const isMine = wrap.dataset.mine === '1';
            if (bubble && !msg.deleted) {
                const editedSuffix = msg.edited_at ? '<span class="text-xs opacity-60 ' + (msg.message ? 'ml-1' : '') + '">(edited)</span>' : '';
                const pinIcon = msg.is_pinned
                    ? '<span class="inline-block mr-1"><svg class="inline h-3 w-3 text-yellow-400" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg></span>'
                    : '';
                const body = msg.message ? '<div class="message-text leading-relaxed">' + esc(msg.message).replace(/\n/g,'<br>') + '</div>' : '';
                const att = (msg.attachments && msg.attachments.length) ? attachmentsHtml(msg, isMine) : '';
                bubble.innerHTML = pinIcon + body + editedSuffix + att;
            }
            // Update 3-dot btn pinned state
            const menuBtn = wrap.querySelector('.msg-menu-btn');
            if (menuBtn) menuBtn.dataset.pinned = msg.is_pinned ? '1' : '0';
        }

        function markMessageDeleted(msgId) {
            const wrap = document.querySelector(`[data-msg-id="${msgId}"]`);
            if (!wrap) return;
            wrap.dataset.text = '';
            const bubble = wrap.querySelector('.msg-bubble');
            if (bubble) {
                bubble.className = 'msg-bubble rounded-2xl px-4 py-2 text-sm shadow-sm bg-gray-200 text-gray-400 italic';
                bubble.textContent = 'Message deleted';
            }
            wrap.querySelector('.msg-menu-wrap')?.remove();
        }

        function updatePinnedStatus(msgId, pinned) {
            const wrap = document.querySelector(`[data-msg-id="${msgId}"]`);
            if (wrap) { wrap.dataset.pinned = pinned ? '1' : '0'; updateMessageBubble({ id: msgId, is_pinned: pinned, message: wrap.dataset.text }); }
        }

        function refreshPinnedBanner(msg, pinned) {
            const banner = document.getElementById('pinned-banner');
            if (!banner) return;
            if (pinned) {
                banner.classList.remove('hidden');
                document.getElementById('pinned-text').textContent = (msg.message || '').substring(0, 60);
            }
        }
        function removePinnedItem(msgId) {
            document.querySelector(`#pinned-list [data-msg-id="${msgId}"]`)?.remove();
            const remaining = document.querySelectorAll('#pinned-list li').length;
            if (remaining === 0) document.getElementById('pinned-banner')?.classList.add('hidden');
        }

        // ── Image lightbox (in-page only; no new browser tab) ─────────────────
        (function messagingImageLightbox() {
            const lb = document.getElementById('img-lightbox');
            const lbMain = document.getElementById('img-lightbox-main');
            const lbThumbs = document.getElementById('img-lightbox-thumbs');
            const lbBackdrop = document.getElementById('img-lightbox-backdrop');
            const lbHeading = document.getElementById('img-lightbox-heading');
            const lbGalleryHint = document.getElementById('img-lightbox-gallery-hint');
            const lbFooter = lb?.querySelector('#img-lightbox-thumbs')?.closest('footer');
            const btnClose = document.getElementById('img-lightbox-close');
            const btnPrev = document.getElementById('img-lightbox-prev');
            const btnNext = document.getElementById('img-lightbox-next');
            const btnDl = document.getElementById('img-lightbox-download');
            const msgContainer = document.getElementById('messages-container');

            if (!lb || !lbMain || !msgContainer) return;

            let items = [];
            let index = 0;

            function downloadHref(src) {
                const base = src.startsWith('http') ? src : (window.location.origin + (src.startsWith('/') ? src : '/' + src));
                return base + (base.includes('?') ? '&' : '?') + 'download=1';
            }

            function collectItems() {
                return Array.from(msgContainer.querySelectorAll('.msg-lightbox-trigger')).map(btn => ({
                    src: btn.getAttribute('data-lightbox-src') || '',
                    name: btn.getAttribute('data-lightbox-name') || 'image',
                })).filter(x => x.src);
            }

            function render() {
                if (!items.length) return;
                const cur = items[index];
                if (!cur) return;
                lbMain.src = cur.src;
                lbMain.alt = cur.name || 'Image';
                if (lbHeading) lbHeading.textContent = cur.name || 'Image preview';

                const multi = items.length > 1;
                if (lbGalleryHint) lbGalleryHint.classList.toggle('hidden', !multi);
                if (lbFooter) lbFooter.classList.toggle('hidden', !multi);

                if (btnPrev) {
                    btnPrev.disabled = !multi;
                    btnPrev.classList.toggle('hidden', !multi);
                }
                if (btnNext) {
                    btnNext.disabled = !multi;
                    btnNext.classList.toggle('hidden', !multi);
                }

                lbThumbs.innerHTML = '';
                items.forEach((it, i) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'relative h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-indigo-400 ' +
                        (i === index ? 'ring-2 ring-white ring-offset-2 ring-offset-black scale-105' : 'ring-1 ring-white/30 opacity-75 hover:opacity-100');
                    const im = document.createElement('img');
                    im.src = it.src;
                    im.alt = '';
                    im.className = 'h-full w-full object-cover';
                    b.appendChild(im);
                    b.addEventListener('click', () => { index = i; render(); });
                    lbThumbs.appendChild(b);
                });
            }

            function openFromTrigger(trigger) {
                items = collectItems();
                const triggers = Array.from(msgContainer.querySelectorAll('.msg-lightbox-trigger'));
                const ti = triggers.indexOf(trigger);
                index = ti >= 0 ? ti : 0;
                if (!items.length) return;
                lb.classList.remove('hidden');
                document.body.classList.add('img-lightbox-open');
                document.body.style.overflow = 'hidden';
                render();
            }

            function closeLb() {
                lb.classList.add('hidden');
                document.body.classList.remove('img-lightbox-open');
                lbMain.removeAttribute('src');
                document.body.style.overflow = '';
                items = [];
                lbThumbs.innerHTML = '';
                if (lbHeading) lbHeading.textContent = 'Image preview';
                if (lbGalleryHint) lbGalleryHint.classList.add('hidden');
                if (lbFooter) lbFooter.classList.remove('hidden');
            }

            msgContainer.addEventListener('click', function (e) {
                const t = e.target.closest('.msg-lightbox-trigger');
                if (!t) return;
                e.preventDefault();
                openFromTrigger(t);
            });

            lbBackdrop?.addEventListener('click', closeLb);
            btnClose?.addEventListener('click', closeLb);
            btnPrev?.addEventListener('click', () => {
                if (items.length < 2) return;
                index = (index - 1 + items.length) % items.length;
                render();
            });
            btnNext?.addEventListener('click', () => {
                if (items.length < 2) return;
                index = (index + 1) % items.length;
                render();
            });
            btnDl?.addEventListener('click', () => {
                const cur = items[index];
                if (!cur) return;
                const a = document.createElement('a');
                a.href = downloadHref(cur.src);
                a.setAttribute('download', cur.name || 'image');
                a.rel = 'noopener';
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            });
            document.addEventListener('keydown', (e) => {
                if (lb.classList.contains('hidden')) return;
                if (e.key === 'ArrowLeft' && items.length > 1) {
                    e.preventDefault();
                    index = (index - 1 + items.length) % items.length;
                    render();
                } else if (e.key === 'ArrowRight' && items.length > 1) {
                    e.preventDefault();
                    index = (index + 1) % items.length;
                    render();
                }
            });

            window.__messagingCloseImageLightbox = closeLb;
        })();

        // ── Scroll to bottom on load ──────────────────────────────────────────
        const mc = document.getElementById('messages-container');
        if (mc) mc.scrollTop = mc.scrollHeight;

        // ── Close all modals on Escape ────────────────────────────────────────
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                const lb = document.getElementById('img-lightbox');
                if (lb && !lb.classList.contains('hidden')) {
                    if (typeof window.__messagingCloseImageLightbox === 'function') {
                        window.__messagingCloseImageLightbox();
                    }
                    return;
                }
                ['modal-new-conv','modal-edit','modal-forward','modal-pinned'].forEach(hideModal);
                closeCtxMenu();
            }
        });

        // ── Polling for new messages every 5 s ───────────────────────────────
        @if($activeConv)
        (function () {
            const convId     = {{ $activeConv->id }};
            const getUrl     = '{{ route("messaging.conversations.messages.get", $activeConv->id) }}';
            const markUrl    = '{{ route("messaging.conversations.read", $activeConv->id) }}';
            let   lastMsgId  = {{ $messages && $messages->count() ? $messages->last()->id : 0 }};
            let   controller = null;
            let   timerId    = null;

            async function poll() {
                // Abort any previous in-flight request to prevent duplicate stacking
                if (controller) { controller.abort(); }
                controller = new AbortController();

                try {
                    const res = await fetch(getUrl + '?page=1', {
                        signal: controller.signal,
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    let gotNew = false;
                    (data.data || []).forEach(msg => {
                        if (msg.id > lastMsgId) {
                            lastMsgId = msg.id;
                            if (!msg.is_mine) { appendMessage(msg); gotNew = true; }
                        }
                    });
                    // Only hit markAsRead when a new message actually arrived
                    if (gotNew) {
                        fetch(markUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        }).catch(() => {});
                    }
                } catch (e) {
                    if (e.name !== 'AbortError') { /* network error – ignore silently */ }
                }
            }

            function startPolling() {
                if (timerId !== null) return;
                timerId = setInterval(poll, 10000);
            }
            function stopPolling() {
                if (timerId === null) return;
                clearInterval(timerId);
                timerId = null;
                if (controller) { controller.abort(); controller = null; }
            }

            // Pause polling when the browser tab goes to background; resume when visible
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) { stopPolling(); }
                else                 { poll(); startPolling(); }
            });

            startPolling();
        })();
        @endif

    })();
    </script>
</x-app-layout>
