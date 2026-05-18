<x-app-layout :embedded="request()->boolean('embedded')">
    @unless(request()->boolean('embedded'))
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    TSHEET Excel Export Preview - Planning Coordinator Reports
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Review your data before downloading in TSHEET format (Super Admin)
                </p>
            </div>
        </div>
    </x-slot>
    @else
    <x-slot name="header"></x-slot>
    @endunless

    <div class="{{ request()->boolean('embedded') ? 'flex min-h-0 flex-1 flex-col py-2' : 'py-6' }}">
        <div class="mx-auto w-full max-w-7xl {{ request()->boolean('embedded') ? 'flex min-h-0 flex-1 flex-col px-0' : 'sm:px-6 lg:px-8' }}">
            <!-- Preview Frame -->
            <div class="{{ request()->boolean('embedded') ? 'mb-0 flex min-h-0 flex-1 flex-col bg-white' : 'mb-6 rounded-lg bg-white shadow' }}">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">TSHEET Format Preview</h3>
                        <div class="flex space-x-2">
                            <form method="POST" action="{{ route('super-admin.campus-user.tsheet.download') }}" class="inline">
                                @csrf
                                <input type="hidden" name="campus_user_filter" value="{{ request()->get('campus_user_filter') }}">
                                @foreach($filters as $key => $value)
                                    @if($value)
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download Excel
                                </button>
                            </form>
                            <a href="{{ route('super-admin.reports.planning-coordinator', collect(request()->only(['campus_user_filter', 'template_code']))->filter(fn ($v) => $v !== null && $v !== '')->all()) }}"
                                @if(request()->boolean('embedded')) target="_top" @endif
                                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Close Preview
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Preview Content -->
                @if(request()->boolean('embedded'))
                <div class="min-h-0 flex-1 overflow-y-auto bg-white px-4 py-4 sm:px-6 sm:py-5">
                        @if(count($tsheetData) > 0)
                            @foreach($tsheetData as $groupKey => $group)
                                <div class="mb-8 {{ !$loop->first ? 'mt-12' : '' }}">
                                    <!-- A) TITLE HEADER SECTION -->
                                    <div class="mb-4">
                                        <div class="text-lg font-bold mb-1">Template Code: {{ $group['title']['template_code'] ?? '' }}</div>
                                        <div class="text-lg font-bold mb-1">Strategic Goal (SG): {{ $group['title']['sg_code'] ?? '' }}</div>
                                        <div class="text-lg font-bold mb-1">Key Result Area (KRA): {{ $group['title']['kra_title'] ?? '' }}</div>
                                        <div class="text-lg font-bold mb-4">Key Performance Indicator (KPI): {{ $group['title']['kpi_title'] ?? '' }}</div>
                                    </div>

                                    <!-- MAIN TSHEET TABLE -->
                                    @if(count($group['main_data_rows']) > 0)
                                    <div class="mb-6">
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Main Data Table</h4>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full border-collapse border border-gray-800 text-xs">
                                                <thead>
                                                    <tr class="bg-gray-200">
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Responsible Work Units</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Quarter</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Program Name</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Major Name</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Google Drive Link For Supporting Documents</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Evidence Verified</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">CI Office Comments</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($group['main_data_rows'] as $row)
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="border border-gray-800 px-2 py-1">{{ $row['responsible_work_units'] ?? '' }}</td>
                                                            <td class="border border-gray-800 px-2 py-1 text-center">{{ $row['quarter'] ?? '' }}</td>
                                                            <td class="border border-gray-800 px-2 py-1">{{ $row['program_name'] ?? '' }}</td>
                                                            <td class="border border-gray-800 px-2 py-1">{{ $row['major_name'] ?? '' }}</td>
                                                            <td class="border border-gray-800 px-2 py-1">
                                                                @if(!empty($row['google_drive_link']))
                                                                    <a href="{{ $row['google_drive_link'] }}" target="_blank" class="text-blue-600 hover:underline">Link</a>
                                                                @endif
                                                            </td>
                                                            <td class="border border-gray-800 px-2 py-1 text-center">{{ $row['evidence_verified'] ?? '' }}</td>
                                                            <td class="border border-gray-800 px-2 py-1">{{ $row['ci_office_comments'] ?? '' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                @if(!$loop->last)
                                    <hr class="my-8 border-t-2 border-gray-300">
                                @endif
                            @endforeach
                        @else
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No data available to preview</p>
                            </div>
                        @endif
                </div>
                @else
                <div class="overflow-auto bg-gray-50 p-6" style="max-height: 80vh;">
                    <div class="bg-white p-6 shadow-sm">
                        @if(count($tsheetData) > 0)
                            @foreach($tsheetData as $groupKey => $group)
                                <div class="mb-8 {{ !$loop->first ? 'mt-12' : '' }}">
                                    <!-- A) TITLE HEADER SECTION -->
                                    <div class="mb-4">
                                        <div class="text-lg font-bold mb-1">Template Code: {{ $group['title']['template_code'] ?? '' }}</div>
                                        <div class="text-lg font-bold mb-1">Strategic Goal (SG): {{ $group['title']['sg_code'] ?? '' }}</div>
                                        <div class="text-lg font-bold mb-1">Key Result Area (KRA): {{ $group['title']['kra_title'] ?? '' }}</div>
                                        <div class="text-lg font-bold mb-4">Key Performance Indicator (KPI): {{ $group['title']['kpi_title'] ?? '' }}</div>
                                    </div>

                                    <!-- MAIN TSHEET TABLE -->
                                    @if(count($group['main_data_rows']) > 0)
                                    <div class="mb-6">
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Main Data Table</h4>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full border-collapse border border-gray-800 text-xs">
                                                <thead>
                                                    <tr class="bg-gray-200">
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Responsible Work Units</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Quarter</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Program Name</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Major Name</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Google Drive Link For Supporting Documents</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">Evidence Verified</th>
                                                        <th class="border border-gray-800 px-2 py-2 text-center font-bold" style="background-color: #e6e6e6;">CI Office Comments</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($group['main_data_rows'] as $row)
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="border border-gray-800 px-2 py-1">{{ $row['responsible_work_units'] ?? '' }}</td>
                                                            <td class="border border-gray-800 px-2 py-1 text-center">{{ $row['quarter'] ?? '' }}</td>
                                                            <td class="border border-gray-800 px-2 py-1">{{ $row['program_name'] ?? '' }}</td>
                                                            <td class="border border-gray-800 px-2 py-1">{{ $row['major_name'] ?? '' }}</td>
                                                            <td class="border border-gray-800 px-2 py-1">
                                                                @if(!empty($row['google_drive_link']))
                                                                    <a href="{{ $row['google_drive_link'] }}" target="_blank" class="text-blue-600 hover:underline">Link</a>
                                                                @endif
                                                            </td>
                                                            <td class="border border-gray-800 px-2 py-1 text-center">{{ $row['evidence_verified'] ?? '' }}</td>
                                                            <td class="border border-gray-800 px-2 py-1">{{ $row['ci_office_comments'] ?? '' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                @if(!$loop->last)
                                    <hr class="my-8 border-t-2 border-gray-300">
                                @endif
                            @endforeach
                        @else
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No data available to preview</p>
                            </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            @unless(request()->boolean('embedded'))
            <!-- Footer Actions -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-600">
                        This is a preview of your TSHEET Excel export. Click "Download Excel" to generate and download the file.
                    </p>
                    <div class="flex space-x-3">
                        <a href="{{ route('super-admin.reports.planning-coordinator', collect(request()->only(['campus_user_filter', 'template_code']))->filter(fn ($v) => $v !== null && $v !== '')->all()) }}"
                            @if(request()->boolean('embedded')) target="_top" @endif
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Close Preview
                        </a>
                        <form method="POST" action="{{ route('super-admin.campus-user.tsheet.download') }}" class="inline">
                            @csrf
                            <input type="hidden" name="campus_user_filter" value="{{ request()->get('campus_user_filter') }}">
                            @foreach($filters as $key => $value)
                                @if($value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <button type="submit" class="inline-flex items-center px-6 py-2 bg-green-600 hover:bg-green-700 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download Excel
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endunless
        </div>
    </div>
</x-app-layout>

