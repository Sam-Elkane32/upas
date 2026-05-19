{{-- Sidebar: design from sidebar-menu (CodingNepal) — dark theme, toggle collapse, tooltips --}}
@php
    $user = auth()->user();
    if ($user->isDeveloper()) {
        $dashboardUrl = route('messaging.developer-tickets.index', ['audience' => 'developers']);
        $profileUrl = route('developer.profile.edit');
        $profileInitials = 'DV';
        $profileColorClass = 'bg-indigo-600';
        $name = trim((string) $user->name);
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name);
            if (count($parts) === 1) {
                $profileInitials = strtoupper(substr($parts[0], 0, min(2, strlen($parts[0]))));
            } else {
                $first = strtoupper(substr($parts[0], 0, 1));
                $last = strtoupper(substr(end($parts), 0, 1));
                $profileInitials = $first . $last;
            }
        }
    } else {
        $dashboardUrl = ($user->isPlanningCoordinator() || $user->hasRole('creator_editor'))
            ? route('campus-user.dashboard')
            : ($user->isAdmin() ? route('campus-admin.dashboard') : ($user->isSuperAdmin() ? route('super-admin.dashboard') : ($user->isViewOnly() ? route('view-only.dashboard') : route('dashboard'))));
        $profileUrl = $user->isSuperAdmin() ? route('super-admin.profile.edit') : ($user->isAdmin() ? route('campus-admin.profile.edit') : ($user->isPlanningCoordinator() || $user->hasRole('creator_editor') ? route('campus-user.profile.edit') : ($user->isViewOnly() ? route('view-only.profile.edit') : route('profile.edit'))));

        // Profile initials and color for avatar (bottom-left), Google-style
        $profileInitials = 'U';
        $profileColorClass = 'bg-gray-400';
        if ($user->isSuperAdmin()) {
            $profileInitials = 'S';
            $profileColorClass = 'bg-blue-600';
        } elseif ($user->isPlanningCoordinator()) {
            $profileInitials = 'PL';
            $profileColorClass = 'bg-green-600';
        } elseif (method_exists($user, 'isQACoordinator') && $user->isQACoordinator()) {
            $profileInitials = 'QA';
            $profileColorClass = 'bg-yellow-500';
        } else {
            $name = trim((string) $user->name);
            if ($name !== '') {
                $parts = preg_split('/\s+/', $name);
                if (count($parts) === 1) {
                    $profileInitials = strtoupper(substr($parts[0], 0, 1));
                } else {
                    $first = strtoupper(substr($parts[0], 0, 1));
                    $last = strtoupper(substr(end($parts), 0, 1));
                    $profileInitials = $first . $last;
                }
            }
        }
    }
@endphp
@php
    /** Developer Support: report form, tickets list, alerts, or repair ticket detail */
    $messagingDeveloperContext = request()->query('audience') === 'developers'
        || request()->routeIs('messaging.repair-tickets.*')
        || request()->routeIs('messaging.developer-tickets.*')
        || request()->routeIs('messaging.developer-notifications.*');
    $isDevelopersNavOpen = $messagingDeveloperContext;
    $isDevelopersSubReportForm = request()->routeIs('messaging.index') && request()->query('audience') === 'developers';
    $isDevelopersSubTickets = request()->routeIs('messaging.developer-tickets.*')
        || request()->routeIs('messaging.repair-tickets.*');
    $isDevelopersSubAlerts = request()->routeIs('messaging.developer-notifications.*');
    $developerTicketNotifyUnread = 0;
    if (auth()->check() && auth()->user()->isDeveloper() && \Illuminate\Support\Facades\Schema::hasTable('notifications')) {
        $developerTicketNotifyUnread = auth()->user()->unreadNotifications()
            ->where('type', \App\Notifications\RepairTicketSubmittedNotification::class)
            ->count();
    }
@endphp
<aside id="sidebar" class="sidebar fixed left-4 top-4 z-30 flex h-[calc(100vh-2rem)] w-[85px] flex-col rounded-2xl border border-gray-200 bg-white shadow-sm transition-all duration-300 ease-out hover:w-[270px]">
    {{-- Header: logo only (labels show on hover) --}}
    <header class="sidebar-header flex shrink-0 items-center justify-center p-4">
        <a href="{{ $dashboardUrl }}" class="header-logo flex min-w-0 flex-shrink items-center overflow-visible">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-gray-50 p-0.5">
                <x-application-logo class="h-full w-full object-contain" />
            </div>
            <span class="nav-label text-xl font-semibold text-gray-800">UPAS</span>
        </a>
    </header>

    {{-- Primary nav --}}
    <nav class="sidebar-nav flex flex-1 flex-col overflow-y-auto px-4 pt-4">
        <ul class="nav-list primary-nav flex flex-col gap-1">
            @if(auth()->user()->isDeveloper())
                @php
                    $reportsConcernsNavActive = request()->routeIs('messaging.developer-tickets.*')
                        || request()->routeIs('messaging.repair-tickets.*')
                        || request()->routeIs('messaging.developer-notifications.*');
                    $isMessagingHome = request()->routeIs('messaging.*') && ! $reportsConcernsNavActive;
                @endphp
                {{-- Developer accounts: inbox for user-submitted repair tickets only (not the staff Report Form) --}}
                <li class="nav-item relative">
                    <a href="{{ route('messaging.developer-tickets.index', ['audience' => 'developers']) }}"
                       class="nav-link flex items-center justify-between gap-2 {{ $reportsConcernsNavActive ? 'active' : '' }}">
                        <span class="flex min-w-0 flex-1 items-center gap-3">
                            <span class="nav-icon shrink-0">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </span>
                            <span class="nav-label truncate">{{ __('Reports & Concerns') }}</span>
                        </span>
                        @if($developerTicketNotifyUnread > 0)
                            <span class="inline-flex min-w-[1.25rem] shrink-0 justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">{{ $developerTicketNotifyUnread > 99 ? '99+' : $developerTicketNotifyUnread }}</span>
                        @endif
                    </a>
                    <span class="nav-tooltip">{{ __('Reports & Concerns') }}</span>
                </li>
                <li class="nav-item relative" id="sidebar-messages-item">
                    <a href="{{ route('messaging.index') }}"
                       class="nav-link {{ $isMessagingHome ? 'active' : '' }}">
                        <span class="nav-icon shrink-0 relative">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <span id="sidebar-msg-badge"
                                  class="absolute -top-1 -right-1 hidden h-4 min-w-[1rem] px-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold items-center justify-center leading-none"></span>
                        </span>
                        <span class="nav-label">{{ __('Messages') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Messages') }}</span>
                </li>
            @else
            @php $isDashboard = request()->routeIs('campus-user.dashboard') || request()->routeIs('campus-admin.dashboard') || request()->routeIs('super-admin.dashboard') || request()->routeIs('view-only.dashboard') || request()->routeIs('dashboard'); @endphp
            <li class="nav-item relative">
                <a href="{{ $dashboardUrl }}" class="nav-link {{ $isDashboard ? 'active' : '' }}">
                    <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg></span>
                    <span class="nav-label">{{ __('Dashboard') }}</span>
                </a>
                <span class="nav-tooltip">{{ __('Dashboard') }}</span>
            </li>

            @if(auth()->user()->isViewOnly())
                <li class="nav-item relative">
                    <a href="{{ route('view-only.submissions.index') }}" class="nav-link {{ request()->routeIs('view-only.submissions.*') ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                        <span class="nav-label">{{ __('Submissions') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Submissions') }}</span>
                </li>
                <li class="nav-item relative">
                    <a href="{{ route('view-only.forms.index') }}" class="nav-link {{ request()->routeIs('view-only.forms.*') ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                        <span class="nav-label">{{ __('Forms') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Published forms') }}</span>
                </li>
                <li class="nav-item relative">
                    <a href="{{ route('view-only.summary.index') }}" class="nav-link {{ request()->routeIs('view-only.summary.*') ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
                        <span class="nav-label">{{ __('Summary') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Summary') }}</span>
                </li>
                @if(auth()->user()->isDivisionLevelViewOnly())
                    <li class="nav-item relative nav-has-submenu {{ $isDevelopersNavOpen ? 'submenu-open' : '' }}">
                        <a href="{{ route('messaging.index', ['audience' => 'developers']) }}" class="nav-link {{ $isDevelopersNavOpen ? 'active' : '' }}">
                            <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3a.75.75 0 01.75.75V5h3V3.75a.75.75 0 011.5 0V5h1.25A1.75 1.75 0 0118 6.75v10.5A1.75 1.75 0 0116.25 19h-8.5A1.75 1.75 0 016 17.25V6.75A1.75 1.75 0 017.75 5H9V3.75A.75.75 0 019.75 3zM7.5 8v9.25c0 .138.112.25.25.25h8.5a.25.25 0 00.25-.25V8h-9zM10 11a.75.75 0 000 1.5h4a.75.75 0 000-1.5h-4zm0 3a.75.75 0 000 1.5h2.5a.75.75 0 000-1.5H10z"/></svg></span>
                            <span class="nav-label">{{ __('Developers') }}</span>
                        </a>
                        <span class="nav-tooltip">{{ __('Developers') }}</span>
                        <ul class="templates-submenu mt-1 space-y-1 pl-10">
                            <li>
                                <a href="{{ route('messaging.index', ['audience' => 'developers']) }}" class="nav-link nav-sublink {{ $isDevelopersSubReportForm ? 'active' : '' }}">
                                    <span class="nav-label">{{ __('Report Form') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('messaging.developer-tickets.index', ['audience' => 'developers']) }}" class="nav-link nav-sublink {{ $isDevelopersSubTickets ? 'active' : '' }}">
                                    <span class="nav-label">{{ __('Tickets') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            @endif

            @if(auth()->user()->isAdmin())
                @php $isDevelopers = request()->routeIs('messaging.*') && $messagingDeveloperContext; @endphp
                <li class="nav-item relative">
                    <a href="{{ route('campus-admin.approvals.index') }}" class="nav-link {{ request()->routeIs('campus-admin.approvals*') ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                        <span class="nav-label">{{ __('Approvals') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Approvals') }}</span>
                </li>
                <li class="nav-item relative nav-has-submenu {{ $isDevelopersNavOpen ? 'submenu-open' : '' }}">
                    <a href="{{ route('messaging.index', ['audience' => 'developers']) }}" class="nav-link {{ $isDevelopersNavOpen ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3a.75.75 0 01.75.75V5h3V3.75a.75.75 0 011.5 0V5h1.25A1.75 1.75 0 0118 6.75v10.5A1.75 1.75 0 0116.25 19h-8.5A1.75 1.75 0 016 17.25V6.75A1.75 1.75 0 017.75 5H9V3.75A.75.75 0 019.75 3zM7.5 8v9.25c0 .138.112.25.25.25h8.5a.25.25 0 00.25-.25V8h-9zM10 11a.75.75 0 000 1.5h4a.75.75 0 000-1.5h-4zm0 3a.75.75 0 000 1.5h2.5a.75.75 0 000-1.5H10z"/></svg></span>
                        <span class="nav-label">{{ __('Developers') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Developers') }}</span>
                    <ul class="templates-submenu mt-1 space-y-1 pl-10">
                        <li>
                            <a href="{{ route('messaging.index', ['audience' => 'developers']) }}" class="nav-link nav-sublink {{ $isDevelopersSubReportForm ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Report Form') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('messaging.developer-tickets.index', ['audience' => 'developers']) }}" class="nav-link nav-sublink {{ $isDevelopersSubTickets ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Tickets') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                {{-- QA Coordinator: Messages after Approvals --}}
                <li class="nav-item relative" id="sidebar-messages-item">
                    <a href="{{ route('messaging.index') }}"
                       class="nav-link {{ request()->routeIs('messaging.*') && !$isDevelopers ? 'active' : '' }}">
                        <span class="nav-icon shrink-0 relative">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <span id="sidebar-msg-badge"
                                  class="absolute -top-1 -right-1 hidden h-4 min-w-[1rem] px-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold items-center justify-center leading-none"></span>
                        </span>
                        <span class="nav-label">{{ __('Messages') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Messages') }}</span>
                </li>
            @endif

            @if(auth()->user()->isPlanningCoordinator())
                @php
                    $isTemplatesSection = request()->routeIs('campus-user.create-submission')
                        || request()->routeIs('campus-user.returned-templates')
                        || request()->routeIs('campus-user.open-template')
                        || request()->routeIs('campus-user.edit-submission')
                        || request()->routeIs('campus-user.show-submission');
                    $isReturnedTemplates = request()->routeIs('campus-user.returned-templates');
                    $isDevelopers = request()->routeIs('messaging.*') && $messagingDeveloperContext;
                @endphp
                <li class="nav-item relative nav-has-submenu {{ $isTemplatesSection ? 'submenu-open' : '' }}">
                    <a href="{{ route('campus-user.create-submission') }}" class="nav-link {{ $isTemplatesSection ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
                        <span class="nav-label">{{ __('Templates') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Templates') }}</span>
                    <ul class="templates-submenu mt-1 space-y-1 pl-10">
                        <li>
                            <a href="{{ route('campus-user.create-submission') }}" class="nav-link nav-sublink {{ $isTemplatesSection && !$isReturnedTemplates ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Assigned') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('campus-user.returned-templates') }}" class="nav-link nav-sublink {{ $isReturnedTemplates ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Returned') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item relative">
                    <a href="{{ route('campus-user.reports') }}" class="nav-link {{ request()->routeIs('campus-user.reports*') || request()->routeIs('campus-user.reports.export*') ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
                        <span class="nav-label">{{ __('Reports') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Reports') }}</span>
                </li>
                <li class="nav-item relative nav-has-submenu {{ $isDevelopersNavOpen ? 'submenu-open' : '' }}">
                    <a href="{{ route('messaging.index', ['audience' => 'developers']) }}" class="nav-link {{ $isDevelopersNavOpen ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3a.75.75 0 01.75.75V5h3V3.75a.75.75 0 011.5 0V5h1.25A1.75 1.75 0 0118 6.75v10.5A1.75 1.75 0 0116.25 19h-8.5A1.75 1.75 0 016 17.25V6.75A1.75 1.75 0 017.75 5H9V3.75A.75.75 0 019.75 3zM7.5 8v9.25c0 .138.112.25.25.25h8.5a.25.25 0 00.25-.25V8h-9zM10 11a.75.75 0 000 1.5h4a.75.75 0 000-1.5h-4zm0 3a.75.75 0 000 1.5h2.5a.75.75 0 000-1.5H10z"/></svg></span>
                        <span class="nav-label">{{ __('Developers') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Developers') }}</span>
                    <ul class="templates-submenu mt-1 space-y-1 pl-10">
                        <li>
                            <a href="{{ route('messaging.index', ['audience' => 'developers']) }}" class="nav-link nav-sublink {{ $isDevelopersSubReportForm ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Report Form') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('messaging.developer-tickets.index', ['audience' => 'developers']) }}" class="nav-link nav-sublink {{ $isDevelopersSubTickets ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Tickets') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                {{-- Planning Coordinator: Messages after Reports --}}
                <li class="nav-item relative" id="sidebar-messages-item">
                    <a href="{{ route('messaging.index') }}"
                       class="nav-link {{ request()->routeIs('messaging.*') && !$isDevelopers ? 'active' : '' }}">
                        <span class="nav-icon shrink-0 relative">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <span id="sidebar-msg-badge"
                                  class="absolute -top-1 -right-1 hidden h-4 min-w-[1rem] px-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold items-center justify-center leading-none"></span>
                        </span>
                        <span class="nav-label">{{ __('Messages') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Messages') }}</span>
                </li>
            @endif

            @if(auth()->user()->isSuperAdmin())
                <li class="nav-item relative">
                    <a href="{{ route('super-admin.users') }}" class="nav-link {{ request()->routeIs('super-admin.users*') ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></span>
                        <span class="nav-label">{{ __('Users') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Users') }}</span>
                </li>
                @php
                    $isFormsSection = request()->routeIs('super-admin.templates.*');
                    $formsTab = request()->get('tab', 'forms');
                @endphp
                <li class="nav-item relative nav-has-submenu {{ $isFormsSection ? 'submenu-open' : '' }}">
                    <a href="{{ route('super-admin.templates.index', ['tab' => 'forms']) }}" class="nav-link {{ $isFormsSection ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                        <span class="nav-label">{{ __('Forms') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Forms') }}</span>
                    <ul class="forms-submenu mt-1 space-y-1 pl-10">
                        <li>
                            <a href="{{ route('super-admin.templates.index', ['tab' => 'forms']) }}" class="nav-link nav-sublink {{ $isFormsSection && $formsTab === 'forms' ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Forms Management') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('super-admin.templates.index', ['tab' => 'create']) }}" class="nav-link nav-sublink {{ $isFormsSection && $formsTab === 'create' ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Create Form') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item relative">
                    <a href="{{ route('super-admin.validated-templates.index') }}" class="nav-link {{ request()->routeIs('super-admin.validated-templates.*') ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg></span>
                        <span class="nav-label">{{ __('Validated Templates') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Validated Templates') }}</span>
                </li>
                @php
                    $isReportsSection = request()->routeIs('super-admin.reports.*');
                @endphp
                <li class="nav-item relative nav-has-submenu {{ $isReportsSection ? 'submenu-open' : '' }}">
                    <a href="{{ route('super-admin.reports.overview') }}" class="nav-link {{ request()->routeIs('super-admin.reports.overview') ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
                        <span class="nav-label">{{ __('Reports & Analytics') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Reports & Analytics') }}</span>
                    <ul class="reports-submenu mt-1 space-y-1 pl-10">
                        <li>
                            <a href="{{ route('super-admin.reports.overview') }}" class="nav-link nav-sublink {{ request()->routeIs('super-admin.reports.overview') ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Overview Dashboard') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('super-admin.reports.qa-coordinator') }}" class="nav-link nav-sublink {{ request()->routeIs('super-admin.reports.qa-coordinator') ? 'active' : '' }}">
                                <span class="nav-label">{{ __('QA Coordinator Reports') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('super-admin.reports.planning-coordinator') }}" class="nav-link nav-sublink {{ request()->routeIs('super-admin.reports.planning-coordinator') ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Planning Coordinator Reports') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('super-admin.reports.summary') }}" class="nav-link nav-sublink {{ request()->routeIs('super-admin.reports.summary') ? 'active' : '' }}">
                                <span class="nav-label">{{ __('Summary of Accomplishments') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                {{-- Super Admin: Messages before Settings --}}
                <li class="nav-item relative" id="sidebar-messages-item">
                    <a href="{{ route('messaging.index') }}"
                       class="nav-link {{ request()->routeIs('messaging.*') ? 'active' : '' }}">
                        <span class="nav-icon shrink-0 relative">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <span id="sidebar-msg-badge"
                                  class="absolute -top-1 -right-1 hidden h-4 min-w-[1rem] px-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold items-center justify-center leading-none"></span>
                        </span>
                        <span class="nav-label">{{ __('Messages') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Messages') }}</span>
                </li>
                <li class="nav-item relative">
                    <a href="{{ route('super-admin.settings.index') }}" class="nav-link {{ request()->routeIs('super-admin.settings*') ? 'active' : '' }}">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                        <span class="nav-label">{{ __('Settings') }}</span>
                    </a>
                    <span class="nav-tooltip">{{ __('Settings') }}</span>
                </li>
            @endif
            @endif
        </ul>

        {{-- Secondary nav (bottom) --}}
        <ul class="nav-list secondary-nav mt-auto pt-4 pb-6">
            <li class="nav-item relative">
                <a href="{{ $profileUrl }}" class="nav-link {{ request()->routeIs('profile.edit', 'super-admin.profile.edit', 'campus-admin.profile.edit', 'campus-user.profile.edit', 'view-only.profile.edit', 'developer.profile.edit') ? 'active' : '' }}">
                    <span class="nav-icon shrink-0">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $profileColorClass }} text-white text-xs font-semibold">
                            {{ $profileInitials }}
                        </span>
                    </span>
                    <span class="nav-label">{{ __('Profile') }}</span>
                </a>
                <span class="nav-tooltip">{{ __('Profile') }}</span>
            </li>
            <li class="nav-item relative">
                <form method="POST" action="{{ route('logout') }}" class="block">
                    @csrf
                    <button type="submit" class="nav-link w-full cursor-pointer border-0 bg-transparent text-left">
                        <span class="nav-icon shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg></span>
                        <span class="nav-label">{{ __('Log Out') }}</span>
                    </button>
                </form>
                <span class="nav-tooltip">{{ __('Log Out') }}</span>
            </li>
        </ul>
    </nav>
</aside>

<style>
/* Sidebar: expand on hover, no arrow button */
#sidebar.sidebar {
    font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
}
/* Labels hidden by default (collapsed), show when sidebar is hovered */
#sidebar.sidebar .sidebar-header .header-logo .nav-label,
#sidebar.sidebar .nav-link .nav-label {
    opacity: 0;
    pointer-events: none;
    overflow: hidden;
    max-width: 0;
    white-space: nowrap;
    transition: opacity 0.25s ease, max-width 0.25s ease;
}
#sidebar.sidebar:hover .sidebar-header .header-logo .nav-label,
#sidebar.sidebar:hover .nav-link .nav-label {
    opacity: 1;
    max-width: 12rem;
}
/* Allow full text / wrapping for submenu labels */
#sidebar.sidebar .nav-link.nav-sublink .nav-label {
    max-width: 100%;
    white-space: normal;
}
#sidebar.sidebar .sidebar-header .header-logo {
    justify-content: center;
}
#sidebar.sidebar:hover .sidebar-header .header-logo {
    justify-content: flex-start;
}
#sidebar.sidebar:hover .sidebar-header {
    justify-content: flex-start;
}
#sidebar.sidebar:hover .sidebar-header {
    padding-left: 1rem;
    padding-right: 1rem;
}
#sidebar.sidebar .header-logo:hover {
    color: #004C99;
}
#sidebar.sidebar .nav-link {
    justify-content: center;
}
#sidebar.sidebar:hover .nav-link {
    justify-content: flex-start;
}
#sidebar.sidebar .nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #374151;
    padding: 12px 15px;
    border-radius: 8px;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.3s ease;
}
#sidebar.sidebar .nav-link:hover {
    color: #004C99;
    background: #E8F0FE;
}
#sidebar.sidebar .nav-link:hover .nav-icon {
    color: #004C99;
}
#sidebar.sidebar .nav-link.active {
    color: #004C99;
    background: #E8F0FE;
}
#sidebar.sidebar .nav-link.active .nav-icon {
    color: #004C99;
}
/* Treat submenu headers as active when opened */
#sidebar.sidebar .nav-item.nav-has-submenu.submenu-open > .nav-link {
    color: #004C99;
    background: #E8F0FE;
}
#sidebar.sidebar .nav-item.nav-has-submenu.submenu-open > .nav-link .nav-icon {
    color: #004C99;
}
/* Tooltips: show when sidebar is NOT hovered (collapsed) */
#sidebar.sidebar .nav-tooltip {
    position: absolute;
    top: 50%;
    left: calc(100% + 25px);
    transform: translateY(-50%);
    opacity: 0;
    pointer-events: none;
    padding: 6px 12px;
    border-radius: 8px;
    white-space: nowrap;
    background: #fff;
    color: #004C99;
    font-size: 0.875rem;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid #E5E7EB;
    transition: opacity 0.2s ease, transform 0.2s ease;
}
#sidebar.sidebar .nav-item:hover .nav-tooltip {
    opacity: 1;
    transform: translateY(-50%);
}
#sidebar.sidebar:hover .nav-tooltip {
    display: none;
}
#sidebar.sidebar .nav-link {
    border-radius: 8px;
}
#sidebar.sidebar:not(:hover) .nav-link {
    border-radius: 12px;
    gap: 0;
}
/* Submenus: animate in when nav item has .submenu-open (toggled by click) */
#sidebar.sidebar .forms-submenu,
#sidebar.sidebar .reports-submenu,
#sidebar.sidebar .templates-submenu {
    opacity: 0;
    transform: translateY(4px);
    max-height: 0;
    overflow: hidden;
    pointer-events: none;
    transition: opacity 0.18s ease-out, transform 0.18s ease-out, max-height 0.18s ease-out;
}
#sidebar.sidebar .nav-item.submenu-open > .forms-submenu,
#sidebar.sidebar .nav-item.submenu-open > .reports-submenu,
#sidebar.sidebar .nav-item.submenu-open > .templates-submenu {
    opacity: 1;
    transform: translateY(0);
    max-height: 320px;
    pointer-events: auto;
}

/* Submenu items: fade/slide with slight stagger for smoother feel */
#sidebar.sidebar .forms-submenu li,
#sidebar.sidebar .reports-submenu li,
#sidebar.sidebar .templates-submenu li {
    opacity: 0;
    transform: translateY(4px);
    transition: opacity 0.18s ease-out, transform 0.18s ease-out;
}
#sidebar.sidebar .nav-item.submenu-open > .forms-submenu li,
#sidebar.sidebar .nav-item.submenu-open > .reports-submenu li,
#sidebar.sidebar .nav-item.submenu-open > .templates-submenu li {
    opacity: 1;
    transform: translateY(0);
}

/* Staggered delays */
#sidebar.sidebar .forms-submenu li:nth-child(1),
#sidebar.sidebar .reports-submenu li:nth-child(1),
#sidebar.sidebar .templates-submenu li:nth-child(1) {
    transition-delay: 0.02s;
}
#sidebar.sidebar .forms-submenu li:nth-child(2),
#sidebar.sidebar .reports-submenu li:nth-child(2),
#sidebar.sidebar .templates-submenu li:nth-child(2) {
    transition-delay: 0.05s;
}
#sidebar.sidebar .forms-submenu li:nth-child(3),
#sidebar.sidebar .reports-submenu li:nth-child(3),
#sidebar.sidebar .templates-submenu li:nth-child(3) {
    transition-delay: 0.08s;
}
#sidebar.sidebar .forms-submenu li:nth-child(4),
#sidebar.sidebar .reports-submenu li:nth-child(4),
#sidebar.sidebar .templates-submenu li:nth-child(4) {
    transition-delay: 0.11s;
}
#sidebar.sidebar .nav-link.nav-sublink {
    justify-content: flex-start;
    padding-top: 8px;
    padding-bottom: 8px;
    padding-left: 1.75rem;
    font-size: 0.875rem;
}
/* Restored expanded state after navigation (same as hover look) */
body.sidebar-expanded #sidebar.sidebar {
    width: 270px;
}
body.sidebar-expanded #sidebar.sidebar .sidebar-header .header-logo .nav-label,
body.sidebar-expanded #sidebar.sidebar .nav-link .nav-label {
    opacity: 1;
    max-width: 12rem;
}
body.sidebar-expanded #sidebar.sidebar .sidebar-header .header-logo,
body.sidebar-expanded #sidebar.sidebar .sidebar-header {
    justify-content: flex-start;
}
body.sidebar-expanded #sidebar.sidebar .sidebar-header {
    padding-left: 1rem;
    padding-right: 1rem;
}
body.sidebar-expanded #sidebar.sidebar .nav-link {
    justify-content: flex-start;
}
body.sidebar-expanded #sidebar.sidebar .nav-tooltip {
    display: none;
}
.sidebar-nav {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.sidebar-nav::-webkit-scrollbar {
    display: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.getElementById('sidebar');
    var storageKey = 'sidebar_expanded';
    var leaveTimer = null;
    function setExpanded(expanded) {
        document.body.classList.toggle('sidebar-expanded', expanded);
        if (expanded) sessionStorage.setItem(storageKey, '1');
        else sessionStorage.removeItem(storageKey);
    }
    sidebar.addEventListener('mouseenter', function() {
        if (leaveTimer) { clearTimeout(leaveTimer); leaveTimer = null; }
        setExpanded(true);
    });
    sidebar.addEventListener('mouseleave', function() {
        leaveTimer = setTimeout(function() {
            leaveTimer = null;
            setExpanded(false);
            // Collapse any open sub-menus when sidebar is left
            sidebar.querySelectorAll('.nav-has-submenu.submenu-open').forEach(function(item) {
                item.classList.remove('submenu-open');
            });
        }, 200);
    });
    sidebar.addEventListener('click', function(e) {
        var submenuLink = e.target.closest('.nav-has-submenu > a.nav-link');
        if (submenuLink) {
            var item = submenuLink.closest('.nav-has-submenu');
            if (item) {
                // When manually toggling a submenu, clear any existing route-based highlights
                sidebar.querySelectorAll('.primary-nav .nav-link.active').forEach(function(link) {
                    if (link !== submenuLink) {
                        link.classList.remove('active');
                    }
                });
                var isOpen = item.classList.contains('submenu-open');
                e.preventDefault();
                if (isOpen) {
                    // Second click on same header: close its submenu
                    item.classList.remove('submenu-open');
                } else {
                    // First click: open this submenu and close others
                    sidebar.querySelectorAll('.nav-has-submenu.submenu-open').forEach(function(other) {
                        if (other !== item) other.classList.remove('submenu-open');
                    });
                    item.classList.add('submenu-open');
                }
            }
        }
        if (e.target.closest('a') || e.target.closest('button')) setExpanded(true);
    }, true);
    if (sessionStorage.getItem(storageKey)) setExpanded(true);
});

/* ── Messaging unread badge (polling every 20 s, skipped on messaging page) ── */
@if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || auth()->user()->isPlanningCoordinator() || auth()->user()->isDivisionLevelViewOnly()))
(function () {
    // Don't poll here when the user is already on the messaging page –
    // the messaging page does its own mark-as-read cycle.
    if (window.location.pathname.startsWith('/messaging')) return;

    const badge    = document.querySelector('#sidebar-messages-item #sidebar-msg-badge');
    const endpoint = '{{ route("messaging.unread-count") }}';

    if (!badge) return;

    let badgeController = null;

    async function refreshBadge() {
        if (badgeController) { badgeController.abort(); }
        badgeController = new AbortController();
        try {
            const res  = await fetch(endpoint, {
                signal: badgeController.signal,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            const count = data.count || 0;
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
                badge.style.display = 'inline-flex';
            } else {
                badge.classList.add('hidden');
                badge.style.display = '';
            }
        } catch (e) {
            if (e.name !== 'AbortError') { /* ignore */ }
        }
    }

    // Pause badge polling when the tab is hidden
    let badgeTimer = null;
    function startBadge() {
        if (badgeTimer !== null) return;
        badgeTimer = setInterval(refreshBadge, 20000);
    }
    function stopBadge() {
        if (badgeTimer === null) return;
        clearInterval(badgeTimer);
        badgeTimer = null;
        if (badgeController) { badgeController.abort(); badgeController = null; }
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { stopBadge(); }
        else                 { refreshBadge(); startBadge(); }
    });

    refreshBadge();
    startBadge();
})();
@endif
</script>
