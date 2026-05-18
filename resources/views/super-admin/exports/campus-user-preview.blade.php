<x-app-layout :embedded="request()->boolean('embedded')">
    @unless(request()->boolean('embedded'))
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Preview Before Export - Planning Coordinator Reports
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Review your export data before downloading (Super Admin)
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
                        <h3 class="text-lg font-medium text-gray-900">Export Preview</h3>
                        <div class="flex space-x-2">
                            @if(($format ?? 'pdf') === 'pdf')
                            <form method="POST" action="{{ route('super-admin.campus-user.export.pdf') }}" class="inline">
                                @csrf
                                <input type="hidden" name="campus_user_filter" value="{{ request()->get('campus_user_filter') }}">
                                @foreach($exportData['filters'] as $key => $value)
                                    @if($value)
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download PDF
                                </button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('reports.export') }}" class="inline">
                                @csrf
                                <input type="hidden" name="format" value="excel">
                                <input type="hidden" name="report_type" value="summary">
                                <input type="hidden" name="campus_user_filter" value="{{ request()->get('campus_user_filter') }}">
                                @foreach($exportData['filters'] as $key => $value)
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
                            @endif
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
                    @include($exportView, $exportData)
                </div>
                @else
                <div class="overflow-auto bg-gray-50 p-6" style="max-height: 80vh;">
                    <div class="bg-white p-6 shadow-sm">
                        @include($exportView, $exportData)
                    </div>
                </div>
                @endif
            </div>

            @unless(request()->boolean('embedded'))
            <!-- Footer Actions -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-600">
                        @if(($format ?? 'pdf') === 'pdf')
                            This is a preview of your export. Click "Download PDF" to generate and download the file.
                        @else
                            This is a preview of your export. Click "Download Excel" to generate and download the file.
                        @endif
                    </p>
                    <div class="flex space-x-3">
                        <a href="{{ route('super-admin.reports.planning-coordinator', collect(request()->only(['campus_user_filter', 'template_code']))->filter(fn ($v) => $v !== null && $v !== '')->all()) }}"
                            @if(request()->boolean('embedded')) target="_top" @endif
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Close Preview
                        </a>
                        @if(($format ?? 'pdf') === 'pdf')
                        <form method="POST" action="{{ route('super-admin.campus-user.export.pdf') }}" class="inline">
                            @csrf
                            <input type="hidden" name="campus_user_filter" value="{{ request()->get('campus_user_filter') }}">
                            @foreach($exportData['filters'] as $key => $value)
                                @if($value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <button type="submit" class="inline-flex items-center px-6 py-2 bg-red-600 hover:bg-red-700 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download PDF
                            </button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('reports.export') }}" class="inline">
                            @csrf
                            <input type="hidden" name="format" value="excel">
                            <input type="hidden" name="report_type" value="summary">
                            <input type="hidden" name="campus_user_filter" value="{{ request()->get('campus_user_filter') }}">
                            @foreach($exportData['filters'] as $key => $value)
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
                        @endif
                    </div>
                </div>
            </div>
            @endunless
        </div>
    </div>
</x-app-layout>

