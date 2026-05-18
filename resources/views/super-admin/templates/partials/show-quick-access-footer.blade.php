    {{-- Quick Access footer: switch to other templates of the same form --}}
    @if($hasQuickAccessFooter)
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-300 shadow-[0_-2px_10px_rgba(0,0,0,0.1)]" id="templateShowFooterBar">
        <div class="flex items-center" id="templateShowTabsContainer">
            <span class="flex-shrink-0 pl-4 pr-2 py-2.5 text-xs font-medium text-gray-500 uppercase tracking-wider">Quick access:</span>
            <div class="flex items-center overflow-x-auto flex-1" id="templateShowTabsScroll" style="scrollbar-width: none; -ms-overflow-style: none;">
                <style>#templateShowTabsScroll::-webkit-scrollbar { display: none; }</style>
                @foreach($quickAccessTemplates as $tmpl)
                    @if($tmpl->id === $template->id)
                        <span class="relative flex-shrink-0 flex items-center px-4 py-2.5 text-sm font-medium border-r border-gray-200 whitespace-nowrap bg-green-50 text-gray-800 ring-inset ring-2 ring-green-400/50">
                            <span>{{ $tmpl->template_code }}</span>
                            <span class="absolute top-0 left-0 right-0 h-0.5 bg-green-500 rounded-t"></span>
                        </span>
                    @else
                        <a href="{{ $readOnly ? route('view-only.templates.field-data', $tmpl->id) : route('super-admin.templates.show', $tmpl) }}" 
                           class="relative flex-shrink-0 flex items-center px-4 py-2.5 text-sm font-medium border-r border-gray-200 transition-colors whitespace-nowrap
                                @if($tmpl->status === 'Published') text-gray-700 hover:bg-green-50 @else text-gray-500 hover:bg-yellow-50 @endif"
                           title="{{ $tmpl->template_code }} - {{ $tmpl->status }}">
                            <span>{{ $tmpl->template_code }}</span>
                            <span class="absolute top-0 left-0 right-0 h-0.5 
                                @if($tmpl->status === 'Published') bg-green-500 @else bg-yellow-500 @endif"></span>
                        </a>
                    @endif
                @endforeach
            </div>
            @if($quickAccessTemplates->count() > 1)
                <span class="flex-shrink-0 px-2 text-xs text-gray-500">({{ $quickAccessTemplates->count() }} templates)</span>
            @endif
            <div class="flex-shrink-0 w-px h-8 bg-gray-300 mx-1"></div>
            <button type="button" onclick="scrollTemplateShowTabs('left')" 
                    class="flex-shrink-0 px-2 py-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors" title="Previous">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <button type="button" onclick="scrollTemplateShowTabs('right')" 
                    class="flex-shrink-0 px-2 py-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors" title="Next">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
    <script>
        function scrollTemplateShowTabs(direction) {
            const container = document.getElementById('templateShowTabsScroll');
            if (!container) return;
            const scrollAmount = 200;
            if (direction === 'left') {
                container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            } else {
                container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            }
        }
    </script>
    @endif
