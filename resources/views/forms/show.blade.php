<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    @php
        $footerTemplates = $form->templates ?? collect([]);
        if (!($footerTemplates instanceof \Illuminate\Support\Collection)) {
            $footerTemplates = collect($footerTemplates ?: []);
        }
        $hasFooterTabs = auth()->user()->isSuperAdmin() && $footerTemplates->count() > 0;
        $hasViewOnlyFooterTabs = auth()->user()->isViewOnly() && $footerTemplates->count() > 0;
        $vpassKpiData = $vpassKpiData ?? [];
        $formTemplatesForLock  = $form->templates;
        $formIsFullyLocked     = $formTemplatesForLock->isNotEmpty() && $formTemplatesForLock->every(fn($t) => $t->is_locked);
    @endphp
    <div class="pt-2 {{ ($hasFooterTabs || $hasViewOnlyFooterTabs) ? 'pb-16' : 'pb-4' }}">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Floating Header Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Form Details
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            View form information and status
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('super-admin.templates.create', ['form_id' => $form->id]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Template
                        </a>

                        {{-- Lock Form / Unlock Form --}}
                        @if($formIsFullyLocked)
                            <form method="POST" action="{{ route('super-admin.forms.unlock', $form) }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 transition ease-in-out duration-150"
                                        onclick="return confirm('Unlock all templates in this form? Planning coordinators will regain access.')">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                    </svg>
                                    Unlock Form
                                </button>
                            </form>
                        @else
                            <button type="button"
                                    onclick="document.getElementById('lock-form-modal').classList.remove('hidden')"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/>
                                </svg>
                                Lock Form
                            </button>
                        @endif
                        @endif
                        @if(auth()->user()->canCreateForms() || auth()->user()->isSuperAdmin())
                        <a href="{{ route('forms.edit', $form->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Edit Form
                        </a>
                        @endif
                        <a href="{{ auth()->user()->isSuperAdmin() ? route('super-admin.templates.index') . '#forms' : (auth()->user()->isViewOnly() ? route('view-only.forms.index') : route('forms.index')) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Back To Forms
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Form title</p>
                            <h3 class="text-2xl font-bold text-gray-900">
                                {{ $form->form_title ?? 'Untitled Form' }}
                            </h3>
                            @if(!empty(trim((string) ($form->division ?? ''))))
                                <p class="text-sm text-gray-600 mt-2">
                                    <span class="font-medium text-gray-700">Division (organizational):</span>
                                    {{ trim($form->division) }}
                                </p>
                            @endif
                            <p class="text-xs text-gray-500 mt-2 max-w-2xl">
                                Responsible work units (CI, CED, Registrar, etc.) appear on each KPI row in the table below — they are not the same as this form title.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Main Form Information -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Strategic Goal -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                    Strategic Goal
                                </h4>
                                <p class="text-sm text-gray-900">{{ $form->strategic_goal ?? 'Not specified' }}</p>
                            </div>

                        </div>

                        <!-- Sidebar Information -->
                        <div class="space-y-4">
                            <!-- Timeline Card -->
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                <h4 class="text-sm font-medium text-green-900 mb-3">Timeline</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-green-700">Created:</span>
                                        <span class="text-sm font-medium text-green-900">{{ $form->created_at->format('M d, Y') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-green-700">Updated:</span>
                                        <span class="text-sm font-medium text-green-900">{{ $form->updated_at->format('M d, Y') }}</span>
                                    </div>
                                    @if($form->creator)
                                    <div class="flex justify-between">
                                        <span class="text-sm text-green-700">Created By:</span>
                                        <span class="text-sm font-medium text-green-900">{{ $form->creator->name ?? 'N/A' }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- KRA and KPI Information (Full Width - below grid) -->
                    <div class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                            KRA and KPI Information
                        </h4>
                        
                        @php
                            $kraKpiData = $form->kra_kpi_data ?? null;
                            if (is_string($kraKpiData)) {
                                $kraKpiData = json_decode($kraKpiData, true);
                            }
                            if (!is_array($kraKpiData)) {
                                $kraKpiData = null;
                            }
                        @endphp
                        
                        @if($kraKpiData && count($kraKpiData) > 0)
                            @php
                                $sgCode = $form->sg_code ?? 'SG1';
                                $sgNumber = str_replace('SG', '', $sgCode);
                                if (empty($sgNumber) || !is_numeric($sgNumber)) {
                                    $sgNumber = '1';
                                }
                            @endphp
                            <div class="space-y-4">
                                @foreach($kraKpiData as $kraIndex => $kraData)
                                    <div class="bg-white p-4 rounded-lg border border-gray-300">
                                        <div class="flex items-center justify-between mb-3">
                                            <h5 class="text-md font-semibold text-gray-800 flex items-center">
                                                <svg class="w-4 h-4 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                                </svg>
                                                KRA {{ $sgNumber }}.{{ $kraIndex + 1 }} - {{ $kraData['kra_title'] ?? 'KRA ' . ($kraIndex + 1) }}
                                            </h5>
                                            @if(isset($kraData['kpis']) && count($kraData['kpis']) > 0)
                                                <button type="button" 
                                                        onclick="toggleKpi('kpi-{{ $kraIndex }}', this)" 
                                                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                                    Show KPI
                                                </button>
                                            @endif
                                        </div>
                                        
                                        @if(isset($kraData['kpis']) && count($kraData['kpis']) > 0)
                                            <div id="kpi-{{ $kraIndex }}" style="display: none;">
                                                <p class="mb-1.5 text-[11px] text-gray-500 md:hidden">Swipe or scroll sideways to see all columns.</p>
                                                <div class="overflow-x-auto overscroll-x-contain rounded-xl border border-gray-200/90 bg-white shadow-sm -mx-0.5 sm:mx-0">
                                                    <table class="w-full min-w-[68rem] border-collapse text-[11px] sm:text-xs">
                                                        <thead>
                                                            <tr class="border-b border-gray-200 bg-stone-50/95">
                                                                <th rowspan="2" class="sticky left-0 z-[1] w-10 min-w-[2.5rem] border-r border-gray-200/80 bg-stone-50 px-1.5 py-2.5 text-center font-semibold text-gray-800 align-middle shadow-[2px_0_6px_-2px_rgba(0,0,0,0.06)] sm:px-2">No.</th>
                                                                <th rowspan="2" class="min-w-[11rem] max-w-[22rem] border-r border-gray-200/80 px-1.5 py-2.5 text-left font-semibold text-gray-800 align-middle whitespace-normal sm:min-w-[14rem] sm:px-2">
                                                                    <abbr title="Key Performance Indicator" class="cursor-help no-underline border-b border-dotted border-gray-400"><span class="inline md:hidden">KPI</span><span class="hidden md:inline">Key Performance Indicator (KPI)</span></abbr>
                                                                </th>
                                                                <th rowspan="2" class="min-w-[7.5rem] border-r border-gray-200/80 px-1.5 py-2.5 text-left font-semibold text-gray-800 align-middle whitespace-normal sm:px-2">
                                                                    <span class="inline lg:hidden" title="Responsible Work Units">Resp. work units</span>
                                                                    <span class="hidden lg:inline">Responsible Work Units</span>
                                                                </th>
                                                                <th rowspan="2" class="w-12 min-w-[3rem] border-r border-gray-200/80 px-1 py-2.5 text-center text-[10px] font-semibold text-gray-800 align-middle sm:text-xs" title="Campus Level / University Level">CL/UL</th>
                                                                <th colspan="5" class="border-x border-amber-200/90 bg-amber-100/95 px-2 py-2 text-center text-[10px] font-bold uppercase tracking-wide text-amber-950 sm:text-xs">Target</th>
                                                                <th colspan="5" class="border-x border-sky-200/90 bg-sky-100/95 px-2 py-2 text-center text-[10px] font-bold uppercase tracking-wide text-sky-950 sm:text-xs">Accomplishment</th>
                                                                <th rowspan="2" class="min-w-[3.5rem] border-l border-gray-200/80 px-1 py-2.5 text-center font-semibold text-gray-800 align-middle sm:px-2">Variance</th>
                                                                <th rowspan="2" class="min-w-[4rem] border-l border-gray-200/80 px-1 py-2.5 text-center font-semibold text-gray-800 align-middle leading-tight sm:min-w-[5rem] sm:px-2" title="Rate of accomplishment"><span class="hidden sm:inline">Rate of accomp.</span><span class="sm:hidden">Rate %</span></th>
                                                                <th rowspan="2" class="min-w-[5rem] border-l border-gray-200/80 px-1 py-2.5 text-center font-semibold text-gray-800 align-middle leading-tight sm:px-2" title="Descriptive rating"><span class="hidden sm:inline">Descriptive rating</span><span class="sm:hidden">Rating</span></th>
                                                            </tr>
                                                            <tr class="border-b-2 border-gray-200 bg-gradient-to-b from-gray-50/90 to-gray-50">
                                                                <th class="min-w-[3.5rem] border-l border-amber-200/70 bg-amber-50/80 px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-amber-900 sm:px-1.5 sm:text-xs">Q1</th>
                                                                <th class="min-w-[3.5rem] border-l border-amber-200/50 bg-amber-50/60 px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-amber-900 sm:px-1.5 sm:text-xs">Q2</th>
                                                                <th class="min-w-[3.5rem] border-l border-amber-200/50 bg-amber-50/60 px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-amber-900 sm:px-1.5 sm:text-xs">Q3</th>
                                                                <th class="min-w-[3.5rem] border-l border-amber-200/50 bg-amber-50/60 px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-amber-900 sm:px-1.5 sm:text-xs">Q4</th>
                                                                <th class="min-w-[3.75rem] border-x border-amber-200/70 bg-amber-100/70 px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-amber-900 sm:px-1.5 sm:text-xs">Total</th>
                                                                <th class="min-w-[3.5rem] border-l border-sky-200/70 bg-sky-50/80 px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-sky-900 sm:px-1.5 sm:text-xs">Q1</th>
                                                                <th class="min-w-[3.5rem] border-l border-sky-200/50 bg-sky-50/60 px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-sky-900 sm:px-1.5 sm:text-xs">Q2</th>
                                                                <th class="min-w-[3.5rem] border-l border-sky-200/50 bg-sky-50/60 px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-sky-900 sm:px-1.5 sm:text-xs">Q3</th>
                                                                <th class="min-w-[3.5rem] border-l border-sky-200/50 bg-sky-50/60 px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-sky-900 sm:px-1.5 sm:text-xs">Q4</th>
                                                                <th class="min-w-[3.75rem] border-x border-sky-200/70 bg-sky-100/70 px-1 py-1.5 text-center text-[10px] font-bold uppercase tracking-wider text-sky-900 sm:px-1.5 sm:text-xs">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100 bg-white text-gray-900">
                                                            @foreach($kraData['kpis'] as $kpiIndex => $kpi)
                                                                @php
                                                                    $kpiQ1 = $kpi['target_q1'] ?? 0;
                                                                    $kpiQ2 = $kpi['target_q2'] ?? 0;
                                                                    $kpiQ3 = $kpi['target_q3'] ?? 0;
                                                                    $kpiQ4 = $kpi['target_q4'] ?? 0;
                                                                    $kpiTotal = $kpi['target_total'] ?? ($kpiQ1 + $kpiQ2 + $kpiQ3 + $kpiQ4);

                                                                    $levelDisplay = $kpi['level_display'] ?? '';
                                                                    if (empty($levelDisplay) && isset($kpi['level'])) {
                                                                        $levels = is_array($kpi['level']) ? $kpi['level'] : [$kpi['level']];
                                                                        if (in_array('CL', $levels, true) && in_array('UL', $levels, true)) {
                                                                            $levelDisplay = 'CL / UL';
                                                                        } elseif (in_array('CL', $levels, true)) {
                                                                            $levelDisplay = 'CL';
                                                                        } elseif (in_array('UL', $levels, true)) {
                                                                            $levelDisplay = 'UL';
                                                                        }
                                                                    }

                                                                    $vkey = $kraIndex . '_' . $kpiIndex;
                                                                    $vpass = $vpassKpiData[$vkey] ?? [];
                                                                    $accompQ1 = $vpass['accomp_q1'] ?? 0;
                                                                    $accompQ2 = $vpass['accomp_q2'] ?? 0;
                                                                    $accompQ3 = $vpass['accomp_q3'] ?? 0;
                                                                    $accompQ4 = $vpass['accomp_q4'] ?? 0;
                                                                    $accompTotal = $vpass['accomp_total'] ?? 0;
                                                                    $variance = $vpass['variance'] ?? 0;
                                                                    $rate = $vpass['rate_of_accomplishment'] ?? 0;
                                                                    $rating = $vpass['descriptive_rating'] ?? '—';

                                                                    $ratingClass = 'bg-gray-100 text-gray-800';
                                                                    if ($rating === 'BELOW TARGET') $ratingClass = 'bg-red-100 text-red-800';
                                                                    elseif ($rating === 'ABOVE TARGET' || $rating === 'MET TARGET') $ratingClass = 'bg-green-100 text-green-800';
                                                                    elseif ($rating === 'NO TARGET' || $rating === 'NO ACCOMPLISHMENT') $ratingClass = 'bg-blue-100 text-blue-800';
                                                                @endphp
                                                                <tr class="group hover:bg-gray-50/60">
                                                                    <td class="sticky left-0 z-[1] border-r border-gray-100 bg-white px-2 py-2 align-top text-center font-medium text-blue-700 shadow-[2px_0_6px_-2px_rgba(0,0,0,0.04)] group-hover:bg-gray-50/60">{{ $kpi['number'] ?? '-' }}</td>
                                                                    <td class="px-2 py-2 align-top text-gray-900 break-words whitespace-pre-wrap">{{ $kpi['title'] ?? '-' }}</td>
                                                                    <td class="px-2 py-2 align-top text-gray-900 break-words">{{ $kpi['responsible_unit'] ?? '-' }}</td>
                                                                    <td class="px-2 py-2 align-top text-center font-semibold text-gray-900">{{ $levelDisplay ?: '-' }}</td>
                                                                    <td class="px-2 py-2 align-top text-center font-semibold text-gray-900">{{ number_format($kpiQ1, 2) }}</td>
                                                                    <td class="px-2 py-2 align-top text-center font-semibold text-gray-900">{{ number_format($kpiQ2, 2) }}</td>
                                                                    <td class="px-2 py-2 align-top text-center font-semibold text-gray-900">{{ number_format($kpiQ3, 2) }}</td>
                                                                    <td class="px-2 py-2 align-top text-center font-semibold text-gray-900">{{ number_format($kpiQ4, 2) }}</td>
                                                                    <td class="px-2 py-2 align-top text-center font-bold text-gray-900">{{ number_format($kpiTotal, 2) }}</td>
                                                                    <td class="px-2 py-2 align-top text-center text-gray-900">{{ number_format($accompQ1, 2) }}</td>
                                                                    <td class="px-2 py-2 align-top text-center text-gray-900">{{ number_format($accompQ2, 2) }}</td>
                                                                    <td class="px-2 py-2 align-top text-center text-gray-900">{{ number_format($accompQ3, 2) }}</td>
                                                                    <td class="px-2 py-2 align-top text-center text-gray-900">{{ number_format($accompQ4, 2) }}</td>
                                                                    <td class="px-2 py-2 align-top text-center font-semibold text-gray-900">{{ number_format($accompTotal, 2) }}</td>
                                                                    <td class="px-2 pr-6 py-2 align-top text-center font-medium text-gray-900">{{ number_format($variance, 2) }}</td>
                                                                    <td class="pl-6 px-2 py-2 align-top text-center font-medium text-gray-900">{{ $rate !== 0 || $accompTotal != 0 ? number_format($rate, 1) . '%' : '—' }}</td>
                                                                    <td class="px-2 py-2 align-top text-center"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $ratingClass }}">{{ $rating }}</span></td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500 ml-6">No KPIs specified for this KRA</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- Fallback to old format for backward compatibility --}}
                            <div class="space-y-4">
                                <div class="bg-white p-4 rounded-lg border border-gray-300">
                                    <div class="flex items-center justify-between mb-3">
                                        <h5 class="text-md font-semibold text-gray-800">
                                            {{ $form->kra_title ?? 'KRA Title' }}
                                        </h5>
                                        @if($form->kpi_title)
                                            <button type="button" 
                                                    onclick="toggleKpi('kpi-fallback', this)" 
                                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                                Show KPI
                                            </button>
                                        @endif
                                    </div>
                                    @if($form->kpi_title)
                                        @php
                                            $kpiParts = explode('; ', $form->kpi_title);
                                        @endphp
                                        <div id="kpi-fallback" class="space-y-2 ml-6" style="display: none;">
                                            @foreach($kpiParts as $kpi)
                                                <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                                    <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ trim($kpi) }}</p>
                                                    @if($form->responsible_unit)
                                                        <p class="text-xs text-gray-600 mt-1">
                                                            <span class="font-medium">Responsible Unit:</span> {{ $form->responsible_unit }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500 ml-6">No KPIs specified</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fixed Footer Template Tabs Bar - Stays visible on scroll, only for Super Admin --}}
    @if(auth()->user()->isSuperAdmin())
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
    @endif

    {{-- Fixed footer template quick access — same placement as Super Admin, read-only links for Division / view-only --}}
    @if(auth()->user()->isViewOnly())
        @php
            $voTabTemplates = $footerTemplates->sortBy('template_code')->values();
            $voFirstPublishedDone = false;
        @endphp
        @if($voTabTemplates->count() > 0)
            <div class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-300 shadow-[0_-2px_10px_rgba(0,0,0,0.1)]" id="templateFooterBarViewOnly">
                <div class="flex items-center" id="templateTabsContainerViewOnly">
                    <span class="flex-shrink-0 pl-4 pr-2 py-2.5 text-xs font-medium text-gray-500 uppercase tracking-wider">Quick access:</span>
                    <div class="flex items-center overflow-x-auto flex-1" id="templateTabsScrollViewOnly" style="scrollbar-width: none; -ms-overflow-style: none;">
                        <style>#templateTabsScrollViewOnly::-webkit-scrollbar { display: none; }</style>
                        @foreach($voTabTemplates as $voTmpl)
                            @php
                                $voPub = ($voTmpl->status ?? '') === 'Published';
                                $voHighlight = $voPub && ! $voFirstPublishedDone;
                                if ($voHighlight) {
                                    $voFirstPublishedDone = true;
                                }
                            @endphp
                            @if($voPub)
                                <a href="{{ auth()->user()->isDivisionLevelViewOnly() ? route('view-only.templates.field-data', $voTmpl->id) : route('view-only.templates.show', $voTmpl->id) }}"
                                   class="relative flex flex-shrink-0 items-center px-4 py-2.5 text-sm font-medium border-r border-gray-200 transition-colors whitespace-nowrap text-gray-700 hover:bg-green-50
                                       {{ $voHighlight ? 'ring-inset ring-2 ring-green-400/50 bg-green-50/80' : '' }}"
                                   title="{{ $voTmpl->template_code }} — {{ $voTmpl->status }}">
                                    <span>{{ $voTmpl->template_code }}</span>
                                    <span class="absolute top-0 left-0 right-0 h-0.5 bg-green-500"></span>
                                </a>
                            @else
                                <span class="relative flex flex-shrink-0 items-center px-4 py-2.5 text-sm font-medium border-r border-gray-200 whitespace-nowrap text-gray-500 cursor-default"
                                      title="Not published — open unavailable">
                                    <span>{{ $voTmpl->template_code }}</span>
                                    <span class="absolute top-0 left-0 right-0 h-0.5 bg-yellow-500"></span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                    @if($voTabTemplates->count() > 1)
                        <span class="flex-shrink-0 px-2 text-xs text-gray-500">({{ $voTabTemplates->count() }} templates)</span>
                    @endif
                    <div class="flex-shrink-0 w-px h-8 bg-gray-300 mx-1"></div>
                    <button type="button" onclick="scrollTemplateTabsViewOnly('left')"
                            class="flex-shrink-0 px-2 py-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors"
                            title="Previous">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <button type="button" onclick="scrollTemplateTabsViewOnly('right')"
                            class="flex-shrink-0 px-2 py-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors"
                            title="Next">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif
    @endif

    <script>
        function toggleKpi(kpiId, button) {
            const kpiContainer = document.getElementById(kpiId);
            if (kpiContainer) {
                if (kpiContainer.style.display === 'none' || kpiContainer.style.display === '') {
                    kpiContainer.style.display = 'block';
                    button.textContent = 'Hide KPI';
                } else {
                    kpiContainer.style.display = 'none';
                    button.textContent = 'Show KPI';
                }
            }
        }

        // Template tabs scroll functionality
        function scrollTemplateTabs(direction) {
            const container = document.getElementById('templateTabsScroll');
            if (!container) return;
            const scrollAmount = 200;
            if (direction === 'left') {
                container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            } else {
                container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            }
        }

        function scrollTemplateTabsViewOnly(direction) {
            const container = document.getElementById('templateTabsScrollViewOnly');
            if (!container) return;
            const scrollAmount = 200;
            container.scrollBy({ left: direction === 'left' ? -scrollAmount : scrollAmount, behavior: 'smooth' });
        }

        // Initialize scroll button visibility
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('templateTabsScroll');
            if (container) {
                function updateScrollButtons() {
                    const scrollLeftBtn = document.getElementById('scrollLeftBtn');
                    if (scrollLeftBtn) {
                        scrollLeftBtn.classList.toggle('hidden', container.scrollLeft <= 0);
                    }
                }
                container.addEventListener('scroll', updateScrollButtons);
                updateScrollButtons();
            }
        });
        
        function toggleTemplateStatus(templateId, currentStatus) {
            const action = currentStatus === 'Published' ? 'unpublish' : 'publish';
            window.showConfirm({
                title: 'Confirm',
                message: 'Are you sure you want to ' + action + ' this template?',
                confirmText: 'Yes',
                cancelText: 'Cancel',
                onConfirm: function() {
                    fetch(`{{ url('/super-admin/templates') }}/${templateId}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            window.showAlert({ title: 'Notice', message: data.message || 'Failed to ' + action + ' template' });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.showAlert({ title: 'Error', message: 'An error occurred while trying to ' + action + ' the template' });
                    });
                }
            });
        }
        
        function deleteTemplate(templateId, templateCode) {
            window.showConfirm({
                title: 'Confirm',
                message: 'Are you sure you want to delete template "' + templateCode + '"? This action cannot be undone.',
                confirmText: 'Yes, delete',
                cancelText: 'Cancel',
                onConfirm: function() {
                    fetch(`{{ url('/super-admin/templates') }}/${templateId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.json().catch(() => ({ success: true }));
                        }
                        return response.json().then(data => ({ success: false, message: data.message || 'Failed to delete template' }));
                    })
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            window.showAlert({ title: 'Error', message: data.message || 'Failed to delete template' });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.location.reload();
                    });
                }
            });
        }
        
    </script>

    {{-- ── Lock Form Modal ─────────────────────────────────── --}}
    @if(auth()->user()->isSuperAdmin() && !$formIsFullyLocked)
    <div id="lock-form-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-gray-800/60" onclick="document.getElementById('lock-form-modal').classList.add('hidden')"></div>
        <div class="relative z-10 bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Lock Form</h3>
                    <p class="text-sm text-gray-500">This will lock all {{ $formTemplatesForLock->count() }} template(s) in this form and block all planning coordinator access.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('super-admin.forms.lock', $form) }}">
                @csrf
                <div class="mb-4">
                    <label for="lock_reason_form" class="block text-sm font-medium text-gray-700 mb-1">
                        Reason <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea id="lock_reason_form" name="lock_reason" rows="3"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-400 focus:ring-red-400 text-sm"
                              placeholder="e.g. Deadline has passed, no further submissions allowed."></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('lock-form-modal').classList.add('hidden')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Confirm Lock Form
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</x-app-layout>
