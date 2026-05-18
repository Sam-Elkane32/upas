<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Summary of Accomplishments - Preview
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Preview of consolidated accomplishments from all campuses
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('super-admin.summary.export-pdf') }}" 
                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export PDF
                </a>
                <a href="{{ route('super-admin.summary.export-excel') }}" 
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export Excel
                </a>
                <a href="{{ route('super-admin.reports.summary') }}" 
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Close Preview
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Preview Content -->
            @php
                /** Server-side breakdown: finalize + form KPI targets + RollupService (matches Form / VPASS). */
                $overallBreakdown = $overall_breakdown;
                $kraSummary = $kra_summary;
                $workUnitSummary = $work_unit_summary;
            @endphp

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 space-y-8">
                    <!-- Header -->
                    <div class="text-center border-b border-gray-300 pb-6">
                        <h1 class="text-3xl font-bold text-gray-900 tracking-wide">
                            SUMMARY OF ACCOMPLISHMENTS
                        </h1>
                        <h2 class="text-xl font-semibold text-gray-700 mt-2">
                            Pangasinan State University
                        </h2>
                        <p class="text-sm text-gray-600 mt-2">
                            Generated: {{ $university_stats['date_generated'] }}
                        </p>
                    </div>

                    <!-- University Statistics & Totals -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                University-Wide Statistics
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ $university_stats['total_campuses'] }}
                                    </div>
                                    <div class="text-sm text-gray-600">Campuses</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">
                                        {{ $university_stats['total_approved_submissions'] }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Reports (submissions + VPASS)
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-purple-600">
                                        {{ $university_stats['total_sgs'] }}
                                    </div>
                                    <div class="text-sm text-gray-600">Strategic Goals</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-indigo-600">
                                        {{ $university_stats['total_kras'] }}
                                    </div>
                                    <div class="text-sm text-gray-600">Key Result Areas</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-orange-600">
                                        {{ $university_stats['total_kpis'] }}
                                    </div>
                                    <div class="text-sm text-gray-600">Key Performance Indicators</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                KPI & Template Totals
                            </h3>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-600">Total KPIs</dt>
                                    <dd class="font-semibold text-gray-900">
                                        {{ $overallBreakdown['total_kpis'] }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-600">Total Accomplishment Templates</dt>
                                    <dd class="font-semibold text-gray-900">
                                        {{ $university_stats['total_approved_submissions'] }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Overall KPI Status Breakdown -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            KPI status (university)
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs md:text-sm border border-gray-200">
                                <thead>
                                    <tr class="bg-gray-100 text-gray-700">
                                        <th class="px-3 py-2 text-left border-b border-gray-200">Category</th>
                                        <th class="px-3 py-2 text-center border-b border-gray-200">Count</th>
                                        <th class="px-3 py-2 text-center border-b border-gray-200">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalKpis = $overallBreakdown['total_kpis'];
                                        $calculatePercentage = function($count, $total) {
                                            if ($total == 0) return '0.00';
                                            return number_format(($count / $total) * 100, 2);
                                        };
                                    @endphp
                                    <tr class="bg-blue-50">
                                        <td class="px-3 py-2 border-b border-gray-200 font-medium text-gray-800">
                                            KPIs with No Target
                                        </td>
                                        <td class="px-3 py-2 text-center border-b border-gray-200 text-gray-900">
                                            {{ $overallBreakdown['no_target'] }}
                                        </td>
                                        <td class="px-3 py-2 text-center border-b border-gray-200 text-gray-900 font-semibold">
                                            {{ $calculatePercentage($overallBreakdown['no_target'], $totalKpis) }}%
                                        </td>
                                    </tr>
                                    <tr class="bg-red-50">
                                        <td class="px-3 py-2 border-b border-gray-200 font-medium text-gray-800">
                                            KPIs with No Accomplishment
                                        </td>
                                        <td class="px-3 py-2 text-center border-b border-gray-200 text-gray-900">
                                            {{ $overallBreakdown['no_accomplishment'] }}
                                        </td>
                                        <td class="px-3 py-2 text-center border-b border-gray-200 text-gray-900 font-semibold">
                                            {{ $calculatePercentage($overallBreakdown['no_accomplishment'], $totalKpis) }}%
                                        </td>
                                    </tr>
                                    <tr class="bg-yellow-50">
                                        <td class="px-3 py-2 border-b border-gray-200 font-medium text-gray-800">
                                            KPIs Below Target
                                        </td>
                                        <td class="px-3 py-2 text-center border-b border-gray-200 text-gray-900">
                                            {{ $overallBreakdown['below_target'] }}
                                        </td>
                                        <td class="px-3 py-2 text-center border-b border-gray-200 text-gray-900 font-semibold">
                                            {{ $calculatePercentage($overallBreakdown['below_target'], $totalKpis) }}%
                                        </td>
                                    </tr>
                                    <tr class="bg-green-50">
                                        <td class="px-3 py-2 border-b border-gray-200 font-medium text-gray-800">
                                            KPIs Met Target
                                        </td>
                                        <td class="px-3 py-2 text-center border-b border-gray-200 text-gray-900">
                                            {{ $overallBreakdown['met_target'] }}
                                        </td>
                                        <td class="px-3 py-2 text-center border-b border-gray-200 text-gray-900 font-semibold">
                                            {{ $calculatePercentage($overallBreakdown['met_target'], $totalKpis) }}%
                                        </td>
                                    </tr>
                                    <tr class="bg-emerald-50">
                                        <td class="px-3 py-2 border-b border-gray-200 font-medium text-gray-800">
                                            KPIs Above Target
                                        </td>
                                        <td class="px-3 py-2 text-center border-b border-gray-200 text-gray-900">
                                            {{ $overallBreakdown['above_target'] }}
                                        </td>
                                        <td class="px-3 py-2 text-center border-b border-gray-200 text-gray-900 font-semibold">
                                            {{ $calculatePercentage($overallBreakdown['above_target'], $totalKpis) }}%
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Summary by KRA -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            By KRA
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs md:text-sm border border-gray-200">
                                <thead>
                                    <tr class="bg-gray-100 text-gray-700">
                                        <th class="px-3 py-2 border-b border-gray-200 text-left">SG Code</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-left">KRA Title</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center">Total KPIs</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-blue-50">No Target</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-blue-50">%</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-red-50">No Accomplishment</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-red-50">%</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-yellow-50">Below Target</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-yellow-50">%</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-green-50">Met Target</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-green-50">%</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-emerald-50">Above Target</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-emerald-50">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($kraSummary as $kraRow)
                                        @php
                                            $kraTotal = $kraRow['total_kpis'];
                                            $kraPercentage = function($count, $total) {
                                                if ($total == 0) return '0.00';
                                                return number_format(($count / $total) * 100, 2);
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 border-b border-gray-200 text-gray-900 font-medium">
                                                {{ $kraRow['sg_code'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-gray-800">
                                                {{ $kraRow['kra_title'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center font-semibold">
                                                {{ $kraRow['total_kpis'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-blue-50">
                                                {{ $kraRow['no_target'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-blue-50 text-xs font-semibold">
                                                {{ $kraPercentage($kraRow['no_target'], $kraTotal) }}%
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-red-50">
                                                {{ $kraRow['no_accomplishment'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-red-50 text-xs font-semibold">
                                                {{ $kraPercentage($kraRow['no_accomplishment'], $kraTotal) }}%
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-yellow-50">
                                                {{ $kraRow['below_target'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-yellow-50 text-xs font-semibold">
                                                {{ $kraPercentage($kraRow['below_target'], $kraTotal) }}%
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-green-50 font-semibold">
                                                {{ $kraRow['met_target'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-green-50 text-xs font-semibold">
                                                {{ $kraPercentage($kraRow['met_target'], $kraTotal) }}%
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-emerald-50">
                                                {{ $kraRow['above_target'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-emerald-50 text-xs font-semibold">
                                                {{ $kraPercentage($kraRow['above_target'], $kraTotal) }}%
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="13" class="px-3 py-4 text-center text-gray-500 border-b border-gray-200">
                                                No KRA data available.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Summary by Responsible Work Unit -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            By responsible unit (as stored)
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs md:text-sm border border-gray-200">
                                <thead>
                                    <tr class="bg-gray-100 text-gray-700">
                                        <th class="px-3 py-2 border-b border-gray-200 text-left">
                                            Responsible Work Unit
                                        </th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center">Total KPIs</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-blue-50">
                                            No Target
                                        </th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-blue-50">%</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-red-50">
                                            No Accomplishment
                                        </th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-red-50">%</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-yellow-50">
                                            Below Target
                                        </th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-yellow-50">%</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-green-50">
                                            Met Target
                                        </th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-green-50">%</th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-emerald-50">
                                            Above Target
                                        </th>
                                        <th class="px-3 py-2 border-b border-gray-200 text-center bg-emerald-50">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($workUnitSummary as $unitRow)
                                        @php
                                            $unitTotal = $unitRow['total_kpis'];
                                            $unitPercentage = function($count, $total) {
                                                if ($total == 0) return '0.00';
                                                return number_format(($count / $total) * 100, 2);
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 border-b border-gray-200 text-gray-900 font-medium">
                                                {{ $unitRow['work_unit'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center font-semibold">
                                                {{ $unitRow['total_kpis'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-blue-50">
                                                {{ $unitRow['no_target'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-blue-50 text-xs font-semibold">
                                                {{ $unitPercentage($unitRow['no_target'], $unitTotal) }}%
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-red-50">
                                                {{ $unitRow['no_accomplishment'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-red-50 text-xs font-semibold">
                                                {{ $unitPercentage($unitRow['no_accomplishment'], $unitTotal) }}%
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-yellow-50">
                                                {{ $unitRow['below_target'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-yellow-50 text-xs font-semibold">
                                                {{ $unitPercentage($unitRow['below_target'], $unitTotal) }}%
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-green-50 font-semibold">
                                                {{ $unitRow['met_target'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-green-50 text-xs font-semibold">
                                                {{ $unitPercentage($unitRow['met_target'], $unitTotal) }}%
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-emerald-50">
                                                {{ $unitRow['above_target'] }}
                                            </td>
                                            <td class="px-3 py-2 border-b border-gray-200 text-center bg-emerald-50 text-xs font-semibold">
                                                {{ $unitPercentage($unitRow['above_target'], $unitTotal) }}%
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="12" class="px-3 py-4 text-center text-gray-500 border-b border-gray-200">
                                                No work unit data available.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @include('super-admin.summary.partials.extended-scorecard', [
                        'extended_overall' => $extended_overall ?? [],
                        'office_summary_by_sg' => $office_summary_by_sg ?? [],
                        'scorecard_performance_matrix' => $scorecard_performance_matrix ?? [],
                        'contributing_form_titles' => $contributing_form_titles ?? [],
                    ])

                    <!-- Campus-Side Distribution Table -->
                    @if(count($campus_stats) > 0)
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                Campus-Side Distribution
                            </h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs md:text-sm border border-gray-200">
                                    <thead>
                                        <tr class="bg-gray-100 text-gray-700">
                                            <th class="px-3 py-2 border-b border-gray-200 text-left">Campus</th>
                                            <th class="px-3 py-2 border-b border-gray-200 text-center">
                                                Total Approved Templates
                                            </th>
                                            <th class="px-3 py-2 border-b border-gray-200 text-center">
                                                Unique KPIs
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($campus_stats as $campus)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 border-b border-gray-200 text-gray-900 font-medium">
                                                    {{ $campus['campus_name'] }}
                                                </td>
                                                <td class="px-3 py-2 border-b border-gray-200 text-center">
                                                    {{ $campus['total_submissions'] }}
                                                </td>
                                                <td class="px-3 py-2 border-b border-gray-200 text-center">
                                                    {{ $campus['unique_kpis'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

