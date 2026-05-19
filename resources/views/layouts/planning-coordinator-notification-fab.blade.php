@php
    $user = auth()->user();
    $fabLimit = 24;
    $fabNotifications = collect();
    $fabUnread = 0;
    if (\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
        $fabNotifications = $user->notifications()->latest()->limit($fabLimit)->get();
        $fabUnread = $user->unreadNotifications()->count();
    }

    $fabIsUnlockedNotif = static function (array $data): bool {
        $t = strtolower((string) ($data['title'] ?? ''));

        return str_contains($t, 'unlock');
    };

    $fabIsLockNotif = static function (array $data): bool {
        $t = strtolower((string) ($data['title'] ?? ''));
        if (str_contains($t, 'unlock')) {
            return false;
        }

        return str_contains($t, 'lock');
    };

    $fabPriorityTone = static function (array $data): string {
        $p = strtolower((string) ($data['priority'] ?? 'normal'));
        if ($p === 'danger') {
            return 'danger';
        }
        if (in_array($p, ['warning', 'urgent'], true)) {
            return 'warning';
        }

        return 'info';
    };

    $fabExtractCodes = static function ($notif): array {
        $data = $notif->data ?? [];
        $codes = [];
        if (!empty($data['template_code'])) {
            $codes[] = (string) $data['template_code'];
        }
        $msg = (string) ($data['message'] ?? '');
        if ($msg !== '' && preg_match_all('/\b(T\d+)\b/i', $msg, $m)) {
            $codes = array_merge($codes, $m[1]);
        }
        if ($msg !== '' && preg_match('/template\s+[\'"]([^\'"]+)[\'"]/i', $msg, $m)) {
            $codes[] = $m[1];
        }

        return array_values(array_unique(array_filter($codes)));
    };

    /** @var \Illuminate\Support\Collection $orderedFabGroups keys = group key, values = array of notifications */
    $orderedFabGroupKeys = [];
    $fabGroups = [];
    foreach ($fabNotifications as $notif) {
        $title = trim((string) (($notif->data['title'] ?? '')));
        $gKey = $title !== '' ? $title : 'single:' . $notif->id;
        if (!isset($fabGroups[$gKey])) {
            $fabGroups[$gKey] = [];
            $orderedFabGroupKeys[] = $gKey;
        }
        $fabGroups[$gKey][] = $notif;
    }
@endphp
<style>
[x-cloak]{display:none !important;}

@keyframes pc-notif-badge-pulse {
    0%   { box-shadow: 0 0 0 0 rgba(239,68,68,0.45); }
    70%  { box-shadow: 0 0 0 7px rgba(239,68,68,0); }
    100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); }
}
.pc-notif-badge-pulse { animation: pc-notif-badge-pulse 1.85s ease-out infinite; }

@keyframes pc-bell-ring {
    0%,100%{ transform:rotate(0); }
    10%    { transform:rotate(18deg); }
    25%    { transform:rotate(-16deg); }
    40%    { transform:rotate(12deg); }
    55%    { transform:rotate(-10deg); }
    70%    { transform:rotate(6deg); }
    85%    { transform:rotate(-3deg); }
}
@keyframes pc-bell-tap {
    0%,100%{ transform:scale(1); }
    40%    { transform:scale(0.82); }
    70%    { transform:scale(1.08); }
}
.pc-bell-ring { animation: pc-bell-ring 0.5s ease-in-out; }
.pc-bell-tap  { animation: pc-bell-tap  0.3s ease-in-out; }

.pc-notif-group-card {
    transition: background-color 0.15s ease, box-shadow 0.15s ease;
}
.pc-notif-group-card:hover {
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
}
</style>

<div id="pc-notif-fab-root"
     class="pointer-events-none fixed top-4 right-4 z-[9999] flex w-max max-w-[calc(100vw-1.5rem)] flex-col items-end"
     x-data="{ open: false }"
     @keydown.escape.window="open = false"
     @click.outside="open = false">

    <button type="button"
            id="pc-notif-fab-trigger"
            @click.stop="open = !open; pcBellAnimate(open)"
            class="pointer-events-auto relative inline-flex items-center justify-center p-1.5 text-[#1a2744] transition hover:text-[#3b5998] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1a2744] focus-visible:ring-offset-2"
            :aria-expanded="open ? 'true' : 'false'"
            aria-haspopup="true"
            aria-label="Open notifications">
        <svg id="pc-bell-icon" class="h-8 w-8 drop-shadow-sm" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
        </svg>
        <span id="pc-notif-fab-badge"
              class="{{ $fabUnread > 0 ? 'pc-notif-badge-pulse' : 'hidden' }} absolute right-0 top-0 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-black leading-none text-white ring-2 ring-white">
            {{ $fabUnread > 9 ? '9+' : $fabUnread }}
        </span>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
         x-cloak
         @click.stop
         class="pointer-events-auto mt-2 w-[min(22rem,calc(100vw-2rem))] shrink-0 origin-top-right overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-black/5">

        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3">
            <div class="min-w-0">
                <p id="pc-notif-fab-headline" class="text-sm font-bold text-gray-900">
                    @if($fabUnread > 0)
                        {{ $fabUnread }} New {{ $fabUnread === 1 ? 'Notification' : 'Notifications' }}
                    @elseif($fabNotifications->count() > 0)
                        All caught up
                    @else
                        No notifications
                    @endif
                </p>
                <p class="mt-0.5 text-xs text-gray-500">Planning updates · Super Admin</p>
            </div>
            @if($fabUnread > 0)
                <button type="button"
                        id="pc-notif-fab-mark-all"
                        onclick="uapsPcFabMarkAllRead()"
                        class="shrink-0 rounded-lg px-2 py-1 text-xs font-semibold text-teal-700 transition hover:bg-teal-50 hover:text-teal-800">
                    Mark all read
                </button>
            @else
                <span id="pc-notif-fab-mark-all" class="hidden"></span>
            @endif
        </div>

        <div id="pc-notif-fab-scroll" class="max-h-[380px] overflow-y-auto divide-y divide-gray-100/80">
            @forelse($orderedFabGroupKeys as $gKey)
                @php
                    $group = $fabGroups[$gKey] ?? [];
                    $lead = $group[0] ?? null;
                    if (!$lead) {
                        continue;
                    }
                    $leadData = $lead->data ?? [];
                    $nTitle = trim((string) ($leadData['title'] ?? ''));
                    $isUnlock = $fabIsUnlockedNotif($leadData);
                    $isLock = $fabIsLockNotif($leadData);
                    $groupUnread = collect($group)->filter(fn ($n) => is_null($n->read_at))->count();
                    $tones = array_map(fn ($n) => $fabPriorityTone($n->data ?? []), $group);
                    $groupTone = in_array('danger', $tones, true) ? 'danger' : (in_array('warning', $tones, true) ? 'warning' : 'info');
                    $codes = [];
                    foreach ($group as $n) {
                        $codes = array_merge($codes, $fabExtractCodes($n));
                    }
                    $codes = array_values(array_unique($codes));
                    sort($codes);
                    $cnt = count($group);
                    $codeCount = count($codes);
                    if ($cnt > 1 && $codeCount > 0) {
                        $summaryTemplates = $codeCount === 1 ? '1 template' : ($codeCount . ' templates');
                        $summaryTemplates .= ' · ' . implode(', ', $codes);
                        if ($cnt > $codeCount) {
                            $summaryTemplates .= ' · ' . $cnt . ' notifications';
                        }
                    } elseif ($cnt > 1) {
                        $summaryTemplates = $cnt . ' updates in this thread';
                    } else {
                        $summaryTemplates = '';
                    }
                    $when = $lead->created_at->format('M j');
                    $fabGroupNotifyIds = collect($group)->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
                    $solo = $cnt === 1 ? $group[0] : null;
                    $soloUnread = $solo && is_null($solo->read_at);
                @endphp
                <div class="pc-notif-group pc-notif-group-card bg-white"
                     x-data="{ expanded: false }"
                     :class="expanded ? 'bg-slate-50/90' : ''">

                    <div @class([
                        'flex gap-3 px-3.5 py-2.5 sm:px-4 sm:py-3 transition-colors',
                        'bg-teal-50/20 hover:bg-teal-50/35' => $soloUnread,
                        'hover:bg-slate-50/50' => !$soloUnread,
                    ])
                         @if($solo) id="fab-notif-{{ $solo->id }}" data-unread="{{ $soloUnread ? '1' : '0' }}" @endif>
                        @if($isUnlock)
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M6.75 21h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 12v6.75A2.25 2.25 0 006.75 21z"/>
                                </svg>
                            </div>
                        @elseif($groupTone === 'danger')
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                                @if($isLock)
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                    </svg>
                                @endif
                            </div>
                        @elseif($groupTone === 'warning')
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                                @if($isLock)
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                @endif
                            </div>
                        @else
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-gray-500">
                                    <span>{{ $when }}</span>
                                    <span class="text-gray-300">·</span>
                                    <span>Super Admin</span>
                                    @if($groupUnread > 0)
                                        <span class="fab-group-unread-dot inline-flex h-1.5 w-1.5 rounded-full bg-teal-500 ring-2 ring-white" title="Unread"></span>
                                    @endif
                                    @if($groupTone === 'danger')
                                        <span class="rounded-md bg-red-100 px-1.5 py-px text-[10px] font-semibold uppercase tracking-wide text-red-700">Action</span>
                                    @elseif($groupTone === 'warning')
                                        <span class="rounded-md bg-amber-100 px-1.5 py-px text-[10px] font-semibold uppercase tracking-wide text-amber-800">Important</span>
                                    @elseif($isUnlock)
                                        <span class="rounded-md bg-emerald-100 px-1.5 py-px text-[10px] font-semibold uppercase tracking-wide text-emerald-800">Available</span>
                                    @endif
                                </div>
                                @if($cnt > 1)
                                    <button type="button"
                                            @click="expanded = !expanded"
                                            class="shrink-0 rounded-md px-1.5 py-0.5 text-[11px] font-semibold text-gray-500 transition hover:bg-slate-100 hover:text-gray-800"
                                            :aria-expanded="expanded ? 'true' : 'false'">
                                        <span x-text="expanded ? 'Hide' : 'Details'"></span>
                                        <span class="tabular-nums text-gray-400" x-text="' (' + {{ $cnt }} + ')'"></span>
                                    </button>
                                @endif
                                <button type="button"
                                        data-pc-fab-dismiss-group-ids='@json($fabGroupNotifyIds)'
                                        class="pc-fab-dismiss-group shrink-0 inline-flex min-h-8 min-w-8 items-center justify-center rounded-md text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                        title="Dismiss all in group" aria-label="Dismiss all in group">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            @if($nTitle !== '')
                                <p class="mt-1 text-[0.8125rem] font-semibold leading-snug text-gray-900">{{ $nTitle }}</p>
                            @endif

                            @if($summaryTemplates !== '')
                                <p class="mt-0.5 text-xs font-medium text-gray-700">{{ $summaryTemplates }}</p>
                            @endif

                            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                                @if($isLock)
                                    Locked by Super Admin · No edits or submissions until unlocked
                                @elseif($isUnlock)
                                    Unlocked by Super Admin · Edits and submissions are allowed again
                                @else
                                    {{ \Illuminate\Support\Str::limit((string) ($leadData['message'] ?? ''), 120) }}
                                @endif
                            </p>

            @if($cnt === 1)
                @php
                    $soloDeadline = $leadData['deadline'] ?? null;
                @endphp
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100/90 pt-2">
                                <div class="text-[10px] text-gray-400">
                                    @if(!empty($soloDeadline) && !$isLock && !$isUnlock)
                                        Due {{ \Carbon\Carbon::parse($soloDeadline)->format('M j, Y') }}
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($soloUnread)
                                        <button type="button"
                                                onclick="uapsPcFabMarkRead('{{ $solo->id }}')"
                                                class="rounded-lg bg-white px-2 py-1 text-[11px] font-semibold text-teal-700 ring-1 ring-teal-200/80 transition hover:bg-teal-50">
                                            Mark read
                                        </button>
                                    @endif
                                </div>
                            </div>
            @else
                            <div class="mt-2 flex flex-wrap items-center justify-end gap-2 border-t border-gray-100/90 pt-2">
                                @if($groupUnread > 0)
                                    <button type="button"
                                            data-pc-fab-mark-group-ids='@json($fabGroupNotifyIds)'
                                            class="pc-fab-mark-group rounded-lg bg-white px-2 py-1 text-[11px] font-semibold text-teal-700 ring-1 ring-teal-200/80 transition hover:bg-teal-50">
                                        Mark group read
                                    </button>
                                @endif
                            </div>

                            <div x-show="expanded"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-0.5"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-0.5"
                                 class="mt-2 space-y-1 overflow-hidden border-t border-dashed border-gray-200/80 pt-2">
                                @foreach($group as $item)
                                    @php
                                        $idata = $item->data ?? [];
                                        $iunread = is_null($item->read_at);
                                        $icode = $idata['template_code'] ?? null;
                                        $ilock = $fabIsLockNotif($idata);
                                        $iunlock = $fabIsUnlockedNotif($idata);
                                        $ilabel = $icode
                                            ? 'Template ' . $icode
                                            : \Illuminate\Support\Str::limit((string) ($idata['title'] ?? 'Item'), 40);
                                    @endphp
                                    <div id="fab-notif-{{ $item->id }}"
                                         data-unread="{{ $iunread ? '1' : '0' }}"
                                         class="flex items-start justify-between gap-2 rounded-lg px-2 py-1.5 text-xs {{ $iunread ? 'bg-teal-50/40' : 'bg-transparent' }} transition hover:bg-slate-100/80">
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-800">{{ $ilabel }}</p>
                                            <p class="text-[11px] text-gray-500">
                                                {{ $item->created_at->format('M j, g:i A') }}
                                                @if($ilock)
                                                    · Locked · no edits
                                                @elseif($iunlock)
                                                    · Unlocked · open for edits
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex shrink-0 gap-1">
                                            @if($iunread)
                                                <button type="button"
                                                        onclick="uapsPcFabMarkRead('{{ $item->id }}')"
                                                        class="rounded px-1.5 py-0.5 text-[10px] font-semibold text-teal-700 hover:bg-teal-50">Read</button>
                                            @endif
                                            <button type="button"
                                                    data-pc-fab-delete-id="{{ $item->id }}"
                                                    class="pc-fab-delete-row inline-flex min-h-8 min-w-8 items-center justify-center rounded-md text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                                    title="Dismiss" aria-label="Dismiss">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div id="pc-notif-fab-empty" class="flex flex-col items-center justify-center px-6 py-10 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-700">No notifications</p>
                    <p class="mt-1 text-xs text-gray-400">You're all caught up.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
window.pcBellAnimate = function (isOpening) {
    const bell = document.getElementById('pc-bell-icon');
    if (!bell) return;
    const cls = isOpening ? 'pc-bell-ring' : 'pc-bell-tap';
    bell.classList.remove('pc-bell-ring', 'pc-bell-tap');
    void bell.offsetWidth;
    bell.classList.add(cls);
    bell.addEventListener('animationend', () => bell.classList.remove(cls), { once: true });
};

(function () {
    const markReadUrl  = (id) => '{{ url("campus-user/notifications") }}/' + encodeURIComponent(id) + '/mark-read';
    const deleteUrl    = (id) => '{{ url("campus-user/notifications") }}/' + encodeURIComponent(id);
    const markAllUrl   = '{{ route("campus-user.notifications.mark-all-read") }}';
    const csrf         = '{{ csrf_token() }}';

    function fabFetchJson(url, options = {}) {
        const headers = Object.assign({
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }, options.headers || {});
        return fetch(url, Object.assign({ credentials: 'same-origin' }, options, { headers }))
            .then(async (r) => {
                let data = {};
                try {
                    data = await r.json();
                } catch (_) {}
                return { ok: r.ok, status: r.status, data };
            });
    }

    /** Laravel web stack + some hosts block DELETE; spoof via POST + _method. */
    function fabDeleteNotificationRequest(id) {
        const fd = new FormData();
        fd.append('_token', csrf);
        fd.append('_method', 'DELETE');
        return fabFetchJson(deleteUrl(id), { method: 'POST', body: fd });
    }

    function fabUnreadCount() {
        return document.querySelectorAll('#pc-notif-fab-scroll [id^="fab-notif-"][data-unread="1"]').length;
    }
    function fabRowCount() {
        return document.querySelectorAll('#pc-notif-fab-scroll [id^="fab-notif-"]').length;
    }

    function syncGroupUnreadUi(groupEl) {
        if (!groupEl) return;
        const any = groupEl.querySelector('[id^="fab-notif-"][data-unread="1"]');
        const dot = groupEl.querySelector('.fab-group-unread-dot');
        if (dot) {
            any ? dot.classList.remove('hidden') : dot.classList.add('hidden');
        }
        const grpBtn = groupEl.querySelector('button.pc-fab-mark-group');
        if (grpBtn) {
            any ? grpBtn.classList.remove('hidden') : grpBtn.classList.add('hidden');
        }
    }

    function setFabHeadline() {
        const el = document.getElementById('pc-notif-fab-headline');
        if (!el) return;
        const n    = fabUnreadCount();
        const rows = fabRowCount();
        if (n > 0)         el.textContent = n + ' New ' + (n === 1 ? 'Notification' : 'Notifications');
        else if (rows > 0) el.textContent = 'All caught up';
        else               el.textContent = 'No notifications';
    }

    function setFabBadge() {
        const badge = document.getElementById('pc-notif-fab-badge');
        if (!badge) return;
        const n = fabUnreadCount();
        if (n <= 0) { badge.classList.add('hidden'); return; }
        badge.classList.remove('hidden');
        badge.textContent = n > 9 ? '9+' : String(n);
    }

    function toggleMarkAllBtn() {
        const btn = document.getElementById('pc-notif-fab-mark-all');
        if (!btn || btn.tagName !== 'BUTTON') return;
        fabUnreadCount() <= 0 ? btn.classList.add('hidden') : btn.classList.remove('hidden');
    }

    function applyFabRowRead(id) {
        const el = document.getElementById('fab-notif-' + id);
        if (!el) return;
        el.setAttribute('data-unread', '0');
        el.classList.remove('bg-teal-50/40', 'bg-teal-50/20', 'hover:bg-teal-50/35');
        if (el.classList.contains('flex') && el.classList.contains('gap-3')) {
            el.classList.add('hover:bg-slate-50/50');
        }
        el.querySelectorAll('button[onclick^="uapsPcFabMarkRead"]').forEach(b => b.remove());
        syncGroupUnreadUi(el.closest('.pc-notif-group'));
    }

    window.uapsPcFabMarkRead = function (id) {
        fabFetchJson(markReadUrl(id), { method: 'POST' })
        .then(({ ok, data }) => {
            if (!ok || !data.success) return;
            applyFabRowRead(id);
            setFabHeadline(); setFabBadge(); toggleMarkAllBtn();
        })
        .catch(() => {});
    };

    window.uapsPcFabMarkGroupRead = function (ids) {
        if (!Array.isArray(ids) || !ids.length) return;
        const unreadIds = ids.filter(id => {
            const el = document.getElementById('fab-notif-' + id);
            return el && el.getAttribute('data-unread') === '1';
        });
        if (!unreadIds.length) return;
        Promise.all(unreadIds.map((id) => fabFetchJson(markReadUrl(id), { method: 'POST' })))
        .then((results) => {
            const ok = results.every(({ ok: rOk, data }) => rOk && data && data.success);
            if (!ok) return;
            unreadIds.forEach(applyFabRowRead);
            setFabHeadline(); setFabBadge(); toggleMarkAllBtn();
        }).catch(() => {});
    };

    window.uapsPcFabDelete = function (id) {
        if (!id || !confirm('Dismiss this notification?')) return;
        fabDeleteNotificationRequest(id)
        .then(({ ok, data }) => {
            if (!ok || !data.success) return;
            const el = document.getElementById('fab-notif-' + id);
            const group = el ? el.closest('.pc-notif-group') : null;
            el?.remove();
            if (group) {
                const left = group.querySelectorAll('[id^="fab-notif-"]');
                if (left.length === 0) group.remove();
            }
            setFabHeadline(); setFabBadge(); toggleMarkAllBtn();
            uapsPcFabEnsureEmptyState();
        })
        .catch(() => {});
    };

    window.uapsPcFabDismissGroup = function (ids) {
        if (!Array.isArray(ids) || !ids.length) return;
        if (!confirm('Dismiss all notifications in this group?')) return;
        Promise.all(ids.map((rawId) => fabDeleteNotificationRequest(String(rawId))))
        .then((results) => {
            if (!results.every(({ ok, data }) => ok && data && data.success)) return;
            const first = document.getElementById('fab-notif-' + String(ids[0]));
            const group = first && first.closest('.pc-notif-group');
            if (group) {
                group.remove();
            } else {
                ids.forEach((i) => document.getElementById('fab-notif-' + String(i))?.remove());
            }
            setFabHeadline(); setFabBadge(); toggleMarkAllBtn();
            uapsPcFabEnsureEmptyState();
        })
        .catch(() => {});
    };

    window.uapsPcFabMarkAllRead = function () {
        if (fabUnreadCount() <= 0) return;
        const fd = new FormData();
        fd.append('_token', csrf);
        fabFetchJson(markAllUrl, { method: 'POST', body: fd })
        .then(({ ok, data }) => {
            if (!ok || !data.success) return;
            document.querySelectorAll('#pc-notif-fab-scroll [id^="fab-notif-"]').forEach(row => {
                const id = row.id.replace('fab-notif-', '');
                if (row.getAttribute('data-unread') === '1') applyFabRowRead(id);
            });
            setFabHeadline(); setFabBadge(); toggleMarkAllBtn();
        })
        .catch(() => {});
    };

    window.uapsPcFabEnsureEmptyState = function () {
        const scroll = document.getElementById('pc-notif-fab-scroll');
        if (!scroll || document.getElementById('pc-notif-fab-empty')) return;
        if (fabRowCount() > 0 || document.querySelector('#pc-notif-fab-scroll .pc-notif-group')) return;
        scroll.innerHTML =
            '<div id="pc-notif-fab-empty" class="flex flex-col items-center justify-center px-6 py-10 text-center">' +
            '<div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">' +
            '<svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' +
            '</div><p class="text-sm font-semibold text-gray-700">No notifications</p>' +
            '<p class="mt-1 text-xs text-gray-400">You\'re all caught up.</p></div>';
    };

    const scrollHost = document.getElementById('pc-notif-fab-scroll');
    if (scrollHost) {
        scrollHost.addEventListener('click', function (e) {
            const rowDel = e.target.closest('[data-pc-fab-delete-id]');
            if (rowDel) {
                e.preventDefault();
                e.stopPropagation();
                const nid = rowDel.getAttribute('data-pc-fab-delete-id');
                if (nid) window.uapsPcFabDelete(nid);
                return;
            }
            const grpDel = e.target.closest('[data-pc-fab-dismiss-group-ids]');
            if (grpDel) {
                e.preventDefault();
                e.stopPropagation();
                let parsed = [];
                try {
                    parsed = JSON.parse(grpDel.getAttribute('data-pc-fab-dismiss-group-ids') || '[]');
                } catch (_) {
                    return;
                }
                if (Array.isArray(parsed) && parsed.length) {
                    window.uapsPcFabDismissGroup(parsed.map(String));
                }
                return;
            }
            const grpRead = e.target.closest('[data-pc-fab-mark-group-ids]');
            if (grpRead) {
                e.preventDefault();
                e.stopPropagation();
                let parsed = [];
                try {
                    parsed = JSON.parse(grpRead.getAttribute('data-pc-fab-mark-group-ids') || '[]');
                } catch (_) {
                    return;
                }
                if (Array.isArray(parsed) && parsed.length) {
                    window.uapsPcFabMarkGroupRead(parsed.map(String));
                }
            }
        });
    }
})();
</script>
