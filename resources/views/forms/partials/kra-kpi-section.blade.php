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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
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

