<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Preview Before Export
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Review your export data before downloading
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Preview Frame -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Export Preview</h3>
                        <div class="flex space-x-2">
                            @if(($format ?? 'pdf') === 'pdf')
                            <form method="POST" action="{{ route('reports.export') }}" class="inline">
                                @csrf
                                <input type="hidden" name="format" value="pdf">
                                <input type="hidden" name="report_type" value="summary">
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
                            <a href="{{ route('campus-user.reports') }}?{{ http_build_query($exportData['filters']) }}" 
                                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Close Preview
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Content -->
                <div class="p-6 bg-gray-50 overflow-auto" style="max-height: 80vh;">
                    <div class="bg-white p-6 shadow-sm">
                        @include($exportView, $exportData)
                    </div>
                </div>
            </div>

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
                        <a href="{{ route('reports.index') }}?{{ http_build_query($exportData['filters']) }}" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Close Preview
                        </a>
                        @if(($format ?? 'pdf') === 'pdf')
                        <form method="POST" action="{{ route('reports.export') }}" class="inline">
                            @csrf
                            <input type="hidden" name="format" value="pdf">
                            <input type="hidden" name="report_type" value="summary">
                            @foreach($exportData['filters'] as $key => $value)
                                @if($value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
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
                            @foreach($exportData['filters'] as $key => $value)
                                @if($value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
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
        </div>
    </div>
</x-app-layout>

