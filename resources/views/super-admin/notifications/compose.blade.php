<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    <div class="pt-2 pb-10 min-h-screen bg-gray-50">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            {{-- ── Top Breadcrumb Bar ───────────────────────────────────── --}}
            <div class="flex items-center justify-between mb-5">
                <nav class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('super-admin.templates.index', ['tab' => 'templates']) }}"
                       class="hover:text-indigo-600 transition">Templates</a>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <a href="{{ route('super-admin.templates.show', $template) }}"
                       class="hover:text-indigo-600 transition">{{ $template->template_code }}</a>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="font-medium text-gray-700">Send Notification</span>
                </nav>
                <a href="{{ route('super-admin.templates.show', $template) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:border-gray-300 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Template
                </a>
            </div>

            {{-- ── Page Title ───────────────────────────────────────────── --}}
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-teal-600 shadow-md">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </span>
                    Send Deadline Notification
                </h1>
                <p class="text-sm text-gray-500 mt-1 ml-13 pl-[52px]">
                    Compose a task reminder for planning coordinators assigned to this template.
                </p>
            </div>

            {{-- Flash handled by the global popup in layouts.flash-popup --}}

            @if($errors->any())
                <div class="mb-5 flex items-start gap-3 px-4 py-3.5 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 shadow-sm">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <ul class="space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ── Two-Column Layout ────────────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- LEFT: Form (2/3) --}}
                <div class="lg:col-span-2">
                    <form method="POST" action="{{ route('super-admin.templates.send-notification', $template) }}" id="notify-form">
                        @csrf

                        {{-- ── Step 1: Message ────────────────────────── --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-5 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                                <span class="flex items-center justify-center w-7 h-7 rounded-full bg-teal-600 text-white text-xs font-bold flex-shrink-0">1</span>
                                <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Compose Message</h2>
                            </div>
                            <div class="p-6 space-y-5">

                                {{-- Fixed title (server-side only; matches email & in-app subject) --}}
                                <div>
                                    <p class="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Notification title
                                    </p>
                                    <div class="flex items-center gap-2 px-4 py-3 rounded-xl border border-teal-100 bg-teal-50/80 text-sm font-semibold text-teal-900">
                                        <svg class="w-4 h-4 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        <span>{{ $template->fixedDeadlineNotificationTitle() }}</span>
                                    </div>
                                </div>

                                {{-- Message --}}
                                <div>
                                    <label for="notif_message" class="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Message
                                        <span class="text-red-500 ml-0.5">*</span>
                                    </label>
                                    <textarea id="notif_message" name="notif_message" rows="5"
                                              class="w-full rounded-xl border-gray-200 bg-gray-50 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500 focus:bg-white transition placeholder-gray-400 resize-none"
                                              placeholder="Describe the task clearly — what needs to be submitted, reviewed, or completed...">{{ old('notif_message') }}</textarea>
                                    <p class="mt-1 text-xs text-gray-400">Be specific about what action is needed.</p>
                                    @error('notif_message')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- ── Step 2: Deadline & Priority ────────────── --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-5 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                                <span class="flex items-center justify-center w-7 h-7 rounded-full bg-teal-600 text-white text-xs font-bold flex-shrink-0">2</span>
                                <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Set Deadline & Priority</h2>
                            </div>
                            <div class="p-6">
                                {{-- Date: modest width (fits picker); Priority: compact row — no duplicate calendar icon (browser keeps native picker control only) --}}
                                <div class="flex flex-col lg:flex-row lg:items-end gap-6 lg:gap-8">

                                    {{-- Deadline --}}
                                    <div class="w-full lg:w-auto lg:flex-shrink-0 lg:max-w-[14rem]">
                                        <label for="notif_deadline" class="block text-sm font-semibold text-gray-700 mb-1.5">
                                            Deadline Date
                                            <span class="text-red-500 ml-0.5">*</span>
                                        </label>
                                        <input type="date" id="notif_deadline" name="notif_deadline"
                                               value="{{ old('notif_deadline') }}"
                                               min="{{ date('Y-m-d') }}"
                                               class="notif-date-input block w-full max-w-full lg:max-w-[14rem] h-11 px-3 rounded-xl border border-gray-200 bg-gray-50 shadow-sm text-sm text-gray-900 leading-normal focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 focus:bg-white transition [color-scheme:light]">
                                        @error('notif_deadline')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Priority --}}
                                    <div class="w-full lg:flex-1 lg:min-w-0 lg:max-w-md">
                                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                            Priority Level
                                            <span class="text-red-500 ml-0.5">*</span>
                                        </label>
                                        <div class="flex flex-wrap sm:flex-nowrap gap-2 sm:gap-3">
                                            <label id="priority-normal-label"
                                                   class="priority-card flex flex-1 min-w-[7rem] flex-row items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition min-h-[2.75rem]
                                                          {{ old('notif_priority', 'normal') === 'normal' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                                <input type="radio" name="notif_priority" value="normal" class="sr-only"
                                                       {{ old('notif_priority', 'normal') === 'normal' ? 'checked' : '' }}>
                                                <svg class="w-4 h-4 flex-shrink-0 {{ old('notif_priority', 'normal') === 'normal' ? 'text-teal-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span class="text-sm font-semibold {{ old('notif_priority', 'normal') === 'normal' ? 'text-teal-700' : 'text-gray-600' }}">Normal</span>
                                            </label>
                                            <label id="priority-urgent-label"
                                                   class="priority-card flex flex-1 min-w-[7rem] flex-row items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition min-h-[2.75rem]
                                                          {{ old('notif_priority') === 'urgent' ? 'border-red-500 bg-red-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                                <input type="radio" name="notif_priority" value="urgent" class="sr-only"
                                                       {{ old('notif_priority') === 'urgent' ? 'checked' : '' }}>
                                                <svg class="w-4 h-4 flex-shrink-0 {{ old('notif_priority') === 'urgent' ? 'text-red-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                                </svg>
                                                <span class="text-sm font-semibold {{ old('notif_priority') === 'urgent' ? 'text-red-700' : 'text-gray-600' }}">Urgent</span>
                                            </label>
                                        </div>
                                        @error('notif_priority')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ── Step 3: Recipients ──────────────────────── --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-5 overflow-hidden">
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-7 h-7 rounded-full bg-teal-600 text-white text-xs font-bold flex-shrink-0">3</span>
                                    <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Select Recipients</h2>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-xs text-gray-500">
                                        <span id="selected-count" class="font-bold text-teal-700">0</span>
                                        / {{ $recipients->count() }} selected
                                    </span>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="selectAllRecipients(true)"
                                                class="text-xs font-semibold text-teal-600 hover:text-teal-800 bg-teal-50 hover:bg-teal-100 px-3 py-1 rounded-full transition">
                                            Select All
                                        </button>
                                        <button type="button" onclick="selectAllRecipients(false)"
                                                class="text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full transition">
                                            Clear
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @if($recipients->count() > 0)
                                {{-- Search --}}
                                <div class="px-5 pt-4 pb-2">
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                        </div>
                                        <input type="text" id="recipient-search"
                                               class="w-full pl-9 pr-4 py-2 rounded-lg border-gray-200 bg-gray-50 text-sm focus:border-teal-500 focus:ring-teal-500 focus:bg-white transition placeholder-gray-400"
                                               placeholder="Search by name, campus, or email…"
                                               oninput="filterRecipients(this.value)">
                                    </div>
                                </div>

                                {{-- List --}}
                                <div class="px-5 pb-4">
                                    <div id="recipient-list" class="space-y-2 max-h-72 overflow-y-auto pr-1">
                                        @foreach($recipients as $recipient)
                                            @php
                                                $isAssigned = $template->assignedUsers->contains('id', $recipient->id)
                                                           || $template->assigned_user_id == $recipient->id;
                                            @endphp
                                            <label class="recipient-row flex items-center gap-3 px-4 py-3 rounded-xl border border-transparent hover:border-teal-200 hover:bg-teal-50/40 cursor-pointer transition group"
                                                   data-name="{{ strtolower($recipient->name) }}"
                                                   data-campus="{{ strtolower($recipient->campus_code ?? '') }}"
                                                   data-email="{{ strtolower($recipient->email) }}">
                                                <input type="checkbox"
                                                       name="user_ids[]"
                                                       value="{{ $recipient->id }}"
                                                       class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500 recipient-checkbox flex-shrink-0"
                                                       {{ in_array($recipient->id, old('user_ids', [])) || $isAssigned ? 'checked' : '' }}>
                                                <div class="flex items-center justify-between flex-1 min-w-0">
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $recipient->name }}</p>
                                                        <p class="text-xs text-gray-500 truncate mt-0.5">
                                                            {{ $recipient->email }}
                                                            @if($recipient->campus_code)
                                                                <span class="mx-1 text-gray-300">·</span>
                                                                <span class="font-medium text-gray-600">{{ $recipient->campus_code }}</span>
                                                            @endif
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center gap-1.5 flex-shrink-0 ml-2">
                                                        @if($isAssigned)
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-teal-100 text-teal-700">
                                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Assigned
                                                            </span>
                                                        @endif
                                                        @if($recipient->campus_code)
                                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                                {{ $recipient->campus_code }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div id="no-results" class="hidden py-6 text-center text-sm text-gray-400">
                                        No recipients match your search.
                                    </div>
                                </div>

                                @error('user_ids')
                                    <div class="px-6 pb-4">
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    </div>
                                @enderror
                            @else
                                <div class="flex flex-col items-center py-10 px-6 text-center">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-500">No active planning coordinators found.</p>
                                </div>
                            @endif
                        </div>

                        {{-- ── Action Buttons ──────────────────────────── --}}
                        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 mt-2 border-t border-gray-100">
                            <a href="{{ route('super-admin.templates.show', $template) }}"
                               class="inline-flex items-center justify-center gap-2 px-5 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-2xl shadow-sm hover:bg-gray-50 hover:border-gray-300 transition w-full sm:w-auto sm:flex-shrink-0">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Cancel
                            </a>
                            {{-- Solid bg-teal-600 is required under the gradient: Tailwind preflight sets buttons to transparent; without a base color, text-white can sit on a near-white page. --}}
                            <button type="submit" id="send-btn"
                                    class="inline-flex items-center justify-center gap-3 flex-shrink-0 whitespace-nowrap min-h-[3rem] w-full sm:w-auto sm:min-w-[13.5rem] px-8 py-3 text-sm font-semibold rounded-2xl shadow-lg shadow-teal-600/20 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 active:scale-[0.99] transition-[box-shadow,transform] duration-200
                                           bg-teal-600 bg-gradient-to-r from-teal-600 to-teal-700 text-white
                                           hover:from-teal-700 hover:to-teal-800 hover:shadow-teal-600/30
                                           disabled:bg-teal-600 disabled:opacity-90 disabled:cursor-wait">
                                <svg class="w-5 h-5 flex-shrink-0 -ml-0.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                <span class="text-white">Send Notification</span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- RIGHT: Template Info sidebar (1/3) --}}
                <div class="lg:col-span-1 space-y-5">

                    {{-- Template Card --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-4 bg-gradient-to-br from-teal-600 to-teal-700">
                            <p class="text-xs font-semibold text-teal-100 uppercase tracking-wider mb-1">Template</p>
                            <p class="text-2xl font-bold text-white">{{ $template->template_code }}</p>
                            <p class="text-sm text-teal-200 mt-0.5">{{ $template->sg_code }}</p>
                        </div>
                        <div class="px-5 py-4 space-y-3">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">KRA Title</p>
                                <p class="text-sm text-gray-700 font-medium">{{ $template->kra_title ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">KPI Title</p>
                                <p class="text-sm text-gray-600 leading-relaxed line-clamp-3">{{ $template->kpi_title }}</p>
                            </div>
                            <div class="pt-2 border-t border-gray-100 flex items-center justify-between">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Status</p>
                                @if($template->is_locked)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/>
                                        </svg>
                                        Locked
                                    </span>
                                @elseif($template->status === 'Published')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Published
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        Unpublished
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Delivery Info Card --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">How it's delivered</h3>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span class="flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">In-App Notification</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Appears on the coordinator's dashboard immediately.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="flex items-center justify-center w-7 h-7 rounded-full bg-purple-100 flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">Email Notification</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Sent to the coordinator's registered email.</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    {{-- Tips Card --}}
                    <div class="bg-amber-50 rounded-2xl border border-amber-200 p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            <h3 class="text-xs font-bold text-amber-800 uppercase tracking-wide">Tips</h3>
                        </div>
                        <ul class="space-y-2 text-xs text-amber-700">
                            <li class="flex items-start gap-2">
                                <span class="font-bold mt-0.5">•</span>
                                Use <strong>Urgent</strong> only for critical deadlines to avoid alert fatigue.
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold mt-0.5">•</span>
                                Pre-assigned coordinators are already checked for convenience.
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold mt-0.5">•</span>
                                Set the deadline to the actual due date so coordinators see the right date in their reminders.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ── Priority card toggle ─────────────────────────────────────
    document.querySelectorAll('.priority-card').forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        radio.addEventListener('change', () => {
            document.querySelectorAll('.priority-card').forEach(l => {
                const r = l.querySelector('input[type="radio"]');
                const svg = l.querySelector('svg');
                const text = l.querySelector('span.font-semibold');
                if (r.value === 'normal') {
                    l.classList.toggle('border-teal-500', r.checked);
                    l.classList.toggle('bg-teal-50', r.checked);
                    l.classList.toggle('border-gray-200', !r.checked);
                    l.classList.toggle('bg-white', !r.checked);
                    if (svg) { svg.classList.toggle('text-teal-600', r.checked); svg.classList.toggle('text-gray-400', !r.checked); }
                    if (text) { text.classList.toggle('text-teal-700', r.checked); text.classList.toggle('text-gray-600', !r.checked); }
                } else {
                    l.classList.toggle('border-red-500', r.checked);
                    l.classList.toggle('bg-red-50', r.checked);
                    l.classList.toggle('border-gray-200', !r.checked);
                    l.classList.toggle('bg-white', !r.checked);
                    if (svg) { svg.classList.toggle('text-red-600', r.checked); svg.classList.toggle('text-gray-400', !r.checked); }
                    if (text) { text.classList.toggle('text-red-700', r.checked); text.classList.toggle('text-gray-600', !r.checked); }
                }
            });
        });
    });

    // ── Select All / Clear ──────────────────────────────────────
    function selectAllRecipients(checked) {
        document.querySelectorAll('.recipient-checkbox').forEach(cb => {
            const row = cb.closest('.recipient-row');
            if (!row || !row.classList.contains('hidden')) cb.checked = checked;
        });
        updateSelectedCount();
    }

    // ── Selected count ──────────────────────────────────────────
    function updateSelectedCount() {
        const count = document.querySelectorAll('.recipient-checkbox:checked').length;
        const el = document.getElementById('selected-count');
        if (el) el.textContent = count;
    }

    // ── Search / filter recipients ──────────────────────────────
    function filterRecipients(query) {
        const q = query.toLowerCase().trim();
        let visible = 0;
        document.querySelectorAll('.recipient-row').forEach(row => {
            const name   = row.dataset.name   || '';
            const campus = row.dataset.campus || '';
            const email  = row.dataset.email  || '';
            const match = !q || name.includes(q) || campus.includes(q) || email.includes(q);
            row.classList.toggle('hidden', !match);
            if (match) visible++;
        });
        document.getElementById('no-results').classList.toggle('hidden', visible > 0);
    }

    // ── Submit with loading state ──────────────────────────────
    document.getElementById('notify-form').addEventListener('submit', function () {
        const btn = document.getElementById('send-btn');
        if (!btn || btn.disabled) return;
        btn.disabled = true;
        btn.classList.add('pointer-events-none');
        btn.innerHTML = `
            <svg class="w-5 h-5 flex-shrink-0 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-white font-semibold">Sending…</span>
        `;
    });

    // ── Init ───────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.recipient-checkbox').forEach(cb => {
            cb.addEventListener('change', updateSelectedCount);
        });
        updateSelectedCount();
    });
    </script>

    <style>
        /* Center date text vertically in Chromium/WebKit (decorative left icon removed — only native picker remains) */
        .notif-date-input::-webkit-datetime-edit-fields-wrapper { padding: 0.125rem 0; }
        .notif-date-input::-webkit-datetime-edit { line-height: 1.25rem; }
    </style>
</x-app-layout>
