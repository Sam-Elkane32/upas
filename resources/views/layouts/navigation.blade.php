<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-3">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ (auth()->user()->isPlanningCoordinator() || auth()->user()->hasRole('creator_editor')) ? route('campus-user.dashboard') : (auth()->user()->isAdmin() ? route('campus-admin.dashboard') : (auth()->user()->isSuperAdmin() ? route('super-admin.dashboard') : (auth()->user()->isViewOnly() ? route('view-only.dashboard') : route('dashboard')))) }}" class="flex items-center p-2 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex-shrink-0 mr-3">
                            <x-application-logo style="height: 50px; width: 50px; max-height: 50px; object-fit: contain;" />
                        </div>
                        <span class="text-lg font-bold text-blue-800 hidden sm:block">UPAS</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:space-x-3 sm:ml-6">
                    @if(auth()->user()->isPlanningCoordinator() || auth()->user()->hasRole('creator_editor'))
                        <x-nav-link :href="route('campus-user.dashboard')" :active="request()->routeIs('campus-user.dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @elseif(auth()->user()->isAdmin())
                        <x-nav-link :href="route('campus-admin.dashboard')" :active="request()->routeIs('campus-admin.dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @elseif(auth()->user()->isSuperAdmin())
                        <x-nav-link :href="route('super-admin.dashboard')" :active="request()->routeIs('super-admin.dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @elseif(auth()->user()->isViewOnly())
                        <x-nav-link :href="route('view-only.dashboard')" :active="request()->routeIs('view-only.dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @endif
                    
            <!-- View-Only User - Read-only access -->
            @if(auth()->user()->isViewOnly())
            <x-nav-link :href="route('view-only.submissions.index')" :active="request()->routeIs('view-only.submissions.*')">
                {{ __('Submissions') }}
            </x-nav-link>
            <x-nav-link :href="route('view-only.forms.index')" :active="request()->routeIs('view-only.forms.*')">
                {{ __('Forms') }}
            </x-nav-link>
            <x-nav-link :href="route('view-only.summary.index')" :active="request()->routeIs('view-only.summary.*')">
                {{ __('Summary') }}
            </x-nav-link>
            @endif
                    
            <!-- QA Coordinator - Review and approve submissions only -->
            @if(auth()->user()->isAdmin())
            <x-nav-link :href="route('campus-admin.approvals.index')" :active="request()->routeIs('campus-admin.approvals*')">
                {{ __('Approvals') }}
            </x-nav-link>
            @endif
            
            <!-- Planning Coordinator - Data encoder and form submitter -->
            @if(auth()->user()->isCreatorEditor())
            <x-nav-link :href="route('campus-user.create-submission')" :active="request()->routeIs('campus-user.create-submission') || request()->routeIs('campus-user.returned-templates')">
                {{ __('Templates') }}
            </x-nav-link>
            <x-nav-link :href="route('campus-user.reports')" :active="request()->routeIs('campus-user.reports*') || request()->routeIs('campus-user.reports.export*')">
                {{ __('Reports') }}
            </x-nav-link>
            @endif
                    
                    @if(auth()->user()->isSuperAdmin())
                    <x-nav-link :href="route('super-admin.users')" :active="request()->routeIs('super-admin.users*')">
                        {{ __('Users') }}
                    </x-nav-link>
                    <x-nav-link :href="route('super-admin.templates.index') . '#forms'" :active="request()->routeIs('super-admin.templates.*')">
                        {{ __('Forms') }}
                    </x-nav-link>
                    <x-nav-link :href="route('super-admin.validated-templates.index')" :active="request()->routeIs('super-admin.validated-templates.*')">
                        {{ __('Validated Templates') }}
                    </x-nav-link>
                    <x-nav-link :href="route('super-admin.reports.overview')" :active="request()->routeIs('super-admin.reports.*')">
                        {{ __('Reports & Analytics') }}
                    </x-nav-link>
                    <x-nav-link :href="route('super-admin.settings.index')" :active="request()->routeIs('super-admin.settings*')">
                        {{ __('Settings') }}
                    </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex flex-col items-end">
                                <div>{{ Auth::user()->name }}</div>
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="auth()->user()->isSuperAdmin() ? route('super-admin.profile.edit') : (auth()->user()->isAdmin() ? route('campus-admin.profile.edit') : (auth()->user()->isPlanningCoordinator() || auth()->user()->hasRole('creator_editor') ? route('campus-user.profile.edit') : (auth()->user()->isViewOnly() ? route('view-only.profile.edit') : route('profile.edit'))))">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @if(auth()->user()->isSuperAdmin())
                        <x-dropdown-link :href="route('super-admin.settings.index')">
                            {{ __('Settings') }}
                        </x-dropdown-link>
                        @endif

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" 
                        type="button"
                        aria-label="Toggle navigation menu"
                        title="Toggle navigation menu"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if(auth()->user()->isPlanningCoordinator() || auth()->user()->hasRole('creator_editor'))
                <x-responsive-nav-link :href="route('campus-user.dashboard')" :active="request()->routeIs('campus-user.dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @elseif(auth()->user()->isAdmin())
                <x-responsive-nav-link :href="route('campus-admin.dashboard')" :active="request()->routeIs('campus-admin.dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @elseif(auth()->user()->isSuperAdmin())
                <x-responsive-nav-link :href="route('super-admin.dashboard')" :active="request()->routeIs('super-admin.dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @elseif(auth()->user()->isViewOnly())
                <x-responsive-nav-link :href="route('view-only.dashboard')" :active="request()->routeIs('view-only.dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @endif
            
            <!-- View-Only User - Read-only access -->
            @if(auth()->user()->isViewOnly())
            <x-responsive-nav-link :href="route('view-only.submissions.index')" :active="request()->routeIs('view-only.submissions.*')">
                {{ __('Submissions') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('view-only.forms.index')" :active="request()->routeIs('view-only.forms.*')">
                {{ __('Forms') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('view-only.summary.index')" :active="request()->routeIs('view-only.summary.*')">
                {{ __('Summary') }}
            </x-responsive-nav-link>
            @endif
            
            <!-- Planning Coordinator (Creator/Editor) - Only they can create submissions -->
            @if(auth()->user()->isCreatorEditor())
            <x-responsive-nav-link :href="route('campus-user.create-submission')" :active="request()->routeIs('campus-user.create-submission') || request()->routeIs('campus-user.returned-templates')">
                {{ __('Templates') }}
            </x-responsive-nav-link>
            @endif
            
            <!-- QA Coordinator - Review and approve submissions only -->
            @if(auth()->user()->isAdmin())
            <x-responsive-nav-link :href="route('campus-admin.approvals.index')" :active="request()->routeIs('campus-admin.approvals*')">
                {{ __('Approvals') }}
            </x-responsive-nav-link>
            @endif
            
            <!-- Planning Coordinator - Data encoder and form submitter -->
            @if(auth()->user()->isCreatorEditor())
            <x-responsive-nav-link :href="route('campus-user.reports')" :active="request()->routeIs('campus-user.reports*') || request()->routeIs('campus-user.reports.export*')">
                {{ __('Reports') }}
            </x-responsive-nav-link>
            @endif
            
            @if(auth()->user()->isSuperAdmin())
            <x-responsive-nav-link :href="route('super-admin.users')" :active="request()->routeIs('super-admin.users*')">
                {{ __('Users') }}
            </x-responsive-nav-link>
            @endif
            
            @if(auth()->user()->isSuperAdmin())
            <x-responsive-nav-link :href="route('super-admin.templates.index') . '#forms'" :active="request()->routeIs('super-admin.templates.*')">
                {{ __('Forms') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('super-admin.validated-templates.index')" :active="request()->routeIs('super-admin.validated-templates.*')">
                {{ __('Validated Templates') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('super-admin.reports.overview')" :active="request()->routeIs('super-admin.reports.*')">
                {{ __('Reports & Analytics') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="auth()->user()->isSuperAdmin() ? route('super-admin.settings.index') : route('settings.index')" :active="request()->routeIs('super-admin.settings*') || request()->routeIs('settings.*')">
                {{ __('Settings') }}
            </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="auth()->user()->isSuperAdmin() ? route('super-admin.profile.edit') : (auth()->user()->isAdmin() ? route('campus-admin.profile.edit') : (auth()->user()->isPlanningCoordinator() || auth()->user()->hasRole('creator_editor') ? route('campus-user.profile.edit') : (auth()->user()->isViewOnly() ? route('view-only.profile.edit') : route('profile.edit'))))">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @if(auth()->user()->isSuperAdmin())
                <x-responsive-nav-link :href="route('super-admin.settings.index')">
                    {{ __('Settings') }}
                </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

