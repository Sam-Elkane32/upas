{{-- Super Admin consistent page header - include with ['title' => '...', 'subtitle' => '...'] --}}
<div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">
                {{ $title ?? 'Page Title' }}
            </h1>
            @if(!empty($subtitle))
                <p class="text-sm text-gray-600 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        @if(!empty($backUrl) || !empty($actions))
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if(!empty($backUrl))
                    <a href="{{ $backUrl }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back
                    </a>
                @endif
{!! $actions ?? '' !!}
            </div>
        @endif
    </div>
</div>
