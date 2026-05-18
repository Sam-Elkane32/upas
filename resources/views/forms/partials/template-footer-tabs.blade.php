@php
    $tabTemplates = $form->templates ?? collect([]);
    if (!($tabTemplates instanceof \Illuminate\Support\Collection)) {
        $tabTemplates = collect($tabTemplates ?: []);
    }
@endphp

@if($tabTemplates->count() > 0)
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-300 shadow-[0_-2px_10px_rgba(0,0,0,0.1)]" id="templateFooterBar">
        <div class="flex items-center" id="templateTabsContainer">
            {{-- Label --}}
            <span class="flex-shrink-0 pl-4 pr-2 py-2.5 text-xs font-medium text-gray-500 uppercase tracking-wider">Quick access:</span>
            {{-- Scrollable Tabs --}}
            <div class="flex items-center overflow-x-auto flex-1" id="templateTabsScroll" style="scrollbar-width: none; -ms-overflow-style: none;">
                <style>#templateTabsScroll::-webkit-scrollbar { display: none; }</style>
                @foreach($tabTemplates as $tmpl)
                    <div class="relative flex-shrink-0" x-data="{ open: false, dropPos: { left: '0px', bottom: '0px' } }" @click.away="open = false">
                        <button type="button" 
                                @click="
                                    open = !open;
                                    if (open) {
                                        let rect = $el.getBoundingClientRect();
                                        dropPos.left = rect.left + 'px';
                                        dropPos.bottom = (window.innerHeight - rect.top + 4) + 'px';
                                    }
                                "
                                class="flex items-center px-4 py-2.5 text-sm font-medium border-r border-gray-200 transition-colors whitespace-nowrap
                                    @if($tmpl->status === 'Published') text-gray-700 hover:bg-green-50 @else text-gray-500 hover:bg-yellow-50 @endif
                                    @if($loop->first) ring-inset ring-2 ring-green-400/50 bg-green-50/80 @endif"
                                title="{{ $tmpl->template_code }} - {{ $tmpl->status }}">
                            <span>{{ $tmpl->template_code }}</span>
                            {{-- Status Underline --}}
                            <span class="absolute top-0 left-0 right-0 h-0.5 
                                @if($tmpl->status === 'Published') bg-green-500 @else bg-yellow-500 @endif"></span>
                        </button>

                        {{-- Dropdown Menu (opens upward, fixed position to avoid overflow clipping) --}}
                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="fixed w-52 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none"
                             :style="'display: none; left: ' + dropPos.left + '; bottom: ' + dropPos.bottom + '; z-index: 9999;'"
                             style="display: none;">
                            <div class="py-1" role="menu">
                                {{-- Template Info Header --}}
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $tmpl->template_code }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                            @if($tmpl->status === 'Published') bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif">
                                            {{ $tmpl->status }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ $tmpl->campus ? $tmpl->campus->name : 'All Campuses' }}</p>
                                </div>
                                <a href="{{ route('super-admin.templates.show', $tmpl) }}" 
                                   @click="open = false"
                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                                   role="menuitem">
                                    <svg class="mr-3 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View Template
                                </a>
                                <button type="button" 
                                        @click="open = false; toggleTemplateStatus({{ $tmpl->id }}, '{{ $tmpl->status }}');"
                                        class="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                                        role="menuitem">
                                    @if($tmpl->status === 'Published')
                                        <svg class="mr-3 h-4 w-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                        Unpublish
                                    @else
                                        <svg class="mr-3 h-4 w-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Publish
                                    @endif
                                </button>
                                <div class="border-t border-gray-100 my-1"></div>
                                <button type="button" 
                                        @click="open = false; deleteTemplate({{ $tmpl->id }}, '{{ $tmpl->template_code }}');"
                                        class="w-full flex items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900"
                                        role="menuitem">
                                    <svg class="mr-3 h-4 w-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Template count --}}
            @if($tabTemplates->count() > 1)
                <span class="flex-shrink-0 px-2 text-xs text-gray-500">({{ $tabTemplates->count() }} templates)</span>
            @endif

            {{-- Separator --}}
            <div class="flex-shrink-0 w-px h-8 bg-gray-300 mx-1"></div>

            {{-- Navigation Arrows --}}
            <button type="button" onclick="scrollTemplateTabs('left')" 
                    class="flex-shrink-0 px-2 py-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors"
                    title="Previous">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <button type="button" onclick="scrollTemplateTabs('right')" 
                    class="flex-shrink-0 px-2 py-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors"
                    title="Next">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
@endif

