<x-app-layout>
    <x-slot name="header">
        @yield('header_title')
    </x-slot>
    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @yield('subnav')
            @yield('content')
        </div>
    </div>
    @stack('scripts')
</x-app-layout>
