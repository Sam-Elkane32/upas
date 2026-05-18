<!-- Statistics -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-blue-500">
        <div class="p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Total Forms</p>
                <p class="text-2xl font-bold text-gray-900">{{ $formStats['total'] ?? 0 }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-purple-500">
        <div class="p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Total Templates</p>
                <p class="text-2xl font-bold text-gray-900">{{ $formStats['total_templates'] ?? 0 }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-md border border-gray-200 p-5 mb-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        Filter Forms
    </h3>
    <form method="GET" action="{{ route('super-admin.templates.index') }}" class="space-y-4">
            <input type="hidden" name="tab" value="forms">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Search
                    </label>
                    <input type="text" name="form_search" value="{{ request('form_search') }}" 
                           placeholder="Form title or KPI..." 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <!-- Strategic Goal Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Strategic Goal
                    </label>
                    <select name="form_sg_code" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">All SG</option>
                        @foreach($formStrategicGoals as $sg)
                            <option value="{{ $sg }}" {{ request('form_sg_code') == $sg ? 'selected' : '' }}>
                                {{ $sg }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap justify-end gap-2 pt-4 border-t border-gray-200">
                <a href="{{ route('super-admin.templates.index', ['tab' => 'forms']) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    Clear
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>
            </div>
        </form>
</div>

<!-- Forms Table -->
<div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
    <div class="p-6">
        @if($forms->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Division</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SG</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">KRAs</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">KPIs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($forms as $form)
                            @php
                                $kraCount = $form->getKraCount();
                                $kpiCount = $form->getKpiCount();
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        @if($form->division)
                                            <div class="font-semibold">{{ $form->division }}</div>
                                        @else
                                            <span class="text-gray-500">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">{{ $form->sg_code ?? '—' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                    {{ $kraCount }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                    {{ $kpiCount }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-800 border border-purple-100">
                                        {{ (int) ($form->templates_count ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $form->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="relative inline-block text-left" x-data="{ open: false }" @click.away="open = false">
                                        <div>
                                            <button type="button" 
                                                    @click="open = !open"
                                                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                                    title="Actions">
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <div x-show="open" 
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="transform opacity-0 scale-95"
                                             x-transition:enter-end="transform opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="transform opacity-100 scale-100"
                                             x-transition:leave-end="transform opacity-0 scale-95"
                                             x-cloak
                                             x-init="$watch('open', value => {
                                                 if (value) {
                                                     $nextTick(() => {
                                                         const button = $el.previousElementSibling.querySelector('button');
                                                         const rect = button.getBoundingClientRect();
                                                         const dropdown = $el;
                                                         const dropdownWidth = 224;
                                                         const dropdownHeight = dropdown.offsetHeight || 200;
                                                         const viewportWidth = window.innerWidth;
                                                         const viewportHeight = window.innerHeight;
                                                         
                                                         let top = rect.bottom + 8;
                                                         let right = viewportWidth - rect.right;
                                                         
                                                         // Adjust if dropdown would go off bottom of screen
                                                         if (top + dropdownHeight > viewportHeight - 8) {
                                                             top = rect.top - dropdownHeight - 8;
                                                             if (top < 8) top = 8;
                                                         }
                                                         
                                                         // Adjust if dropdown would go off right of screen
                                                         if (right + dropdownWidth > viewportWidth - 8) {
                                                             right = viewportWidth - rect.left - dropdownWidth;
                                                             if (right < 8) right = 8;
                                                         }
                                                         
                                                         dropdown.style.position = 'fixed';
                                                         dropdown.style.top = top + 'px';
                                                         dropdown.style.right = right + 'px';
                                                         dropdown.style.left = 'auto';
                                                     });
                                                 }
                                             })"
                                             class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                                             style="display: none;">
                                            <div class="py-1" role="menu" aria-orientation="vertical">
                                                <!-- View -->
                                                <a href="{{ route('forms.show', $form->id) }}" 
                                                   @click="open = false"
                                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                                                   role="menuitem">
                                                    <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View Form
                                                </a>
                                                
                                                <!-- Edit -->
                                                <a href="{{ route('forms.edit', $form->id) }}" 
                                                   @click="open = false"
                                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                                                   role="menuitem">
                                                    <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Edit Form
                                                </a>
                                                
                                                
                                                <!-- Delete -->
                                                <form method="POST" action="{{ route('super-admin.forms.destroy', $form->id) }}" 
                                                      data-confirm="Are you sure you want to delete this form? This action cannot be undone."
                                                      class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            @click="open = false"
                                                            class="w-full flex items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900"
                                                            role="menuitem">
                                                        <svg class="mr-3 h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                        Delete Form
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $forms->appends(['tab' => 'forms'])->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No forms found</h3>
                <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or create a new form.</p>
            </div>
        @endif
    </div>
</div>

