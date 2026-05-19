<!DOCTYPE html>
@php $isEmbeddedLayout = !empty($embedded ?? false); @endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['h-full' => $isEmbeddedLayout])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @include('partials.vite-production-assets')
        
        <!-- CSRF Token Refresh Script -->
        <script>
            // Auto-refresh CSRF token every 2 hours
            setInterval(function() {
                fetch('{{ route("dashboard") }}', {
                    method: 'HEAD',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(function(response) {
                    const token = response.headers.get('X-CSRF-TOKEN');
                    if (token) {
                        document.querySelector('meta[name="csrf-token"]').setAttribute('content', token);
                        // Update all forms with new token
                        document.querySelectorAll('input[name="_token"]').forEach(function(input) {
                            input.value = token;
                        });
                    }
                }).catch(function(error) {
                    console.error('CSRF token refresh failed:', error);
                });
            }, 7200000); // 2 hours

            // Handle 419 errors globally
            document.addEventListener('DOMContentLoaded', function() {
                // Intercept fetch requests
                const originalFetch = window.fetch;
                window.fetch = function(...args) {
                    return originalFetch.apply(this, args).catch(function(error) {
                        if (error.status === 419) {
                            // CSRF token expired - refresh and retry
                            return fetch('{{ route("dashboard") }}', {
                                method: 'HEAD',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }).then(function(response) {
                                const token = response.headers.get('X-CSRF-TOKEN');
                                if (token) {
                                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', token);
                                    // Update request headers and retry
                                    if (args[1] && args[1].headers) {
                                        args[1].headers['X-CSRF-TOKEN'] = token;
                                    }
                                    return originalFetch.apply(this, args);
                                }
                                throw error;
                            });
                        }
                        throw error;
                    });
                };
            });
        </script>
        <style>
            body.sidebar-expanded #main-content-wrap { padding-left: 17.875rem; }
            @if($isEmbeddedLayout)
            html, body.export-embed-body { height: 100%; }
            body.export-embed-body > div { min-height: 100%; height: 100%; }
            body.export-embed-body #main-content-wrap { flex: 1; min-height: 0; display: flex; flex-direction: column; }
            body.export-embed-body main { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
            @endif
        </style>
    </head>
    <body class="font-sans antialiased @if($isEmbeddedLayout) export-embed-body h-full min-h-0 flex flex-col @endif">
        @unless($isEmbeddedLayout)
        <script>
        (function(){ var k='sidebar_expanded'; if(sessionStorage.getItem(k)) document.body.classList.add('sidebar-expanded'); })();
        </script>
        @endunless
        <div class="bg-gray-100 flex {{ $isEmbeddedLayout ? 'min-h-0 h-full flex-1 flex-col' : 'min-h-screen' }}">
            @unless($isEmbeddedLayout)
                @include('layouts.sidebar')
            @endunless

            {{-- Main content area (offset by sidebar: 16px + 270px expanded, 16px + 85px collapsed) --}}
            <div id="main-content-wrap" class="relative flex flex-1 flex-col min-w-0 {{ $isEmbeddedLayout ? 'w-full' : 'pl-[6.3125rem] transition-[padding] duration-300 ease-out' }}">
                @include('layouts.flash-popup')
                @include('layouts.confirm-dialog')

                <!-- Page Content -->
                <main class="flex-1 {{ $isEmbeddedLayout ? 'p-0 sm:p-0' : 'p-4 sm:p-6 lg:p-8' }}">
                    {{ $slot }}
                </main>
            </div>
        </div>
        {{-- Bell/dropdown outside layout flex so width is never stretched to main column --}}
        @includeWhen(auth()->check() && request()->routeIs('campus-user.*'), 'layouts.planning-coordinator-notification-fab')
    </body>
</html>
