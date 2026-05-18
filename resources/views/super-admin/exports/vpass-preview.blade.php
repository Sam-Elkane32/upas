<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Preview Before Export - VPASS Format
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Review your VPASS Master KPI Matrix before downloading (Super Admin)
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
                        <h3 class="text-lg font-medium text-gray-900">VPASS Format Preview</h3>
                        <div class="flex space-x-2">
                            @php
                                $format = request()->get('format', 'pdf');
                                $isExcel = $format === 'excel';
                                $buttonText = $isExcel ? 'Download Excel' : 'Download PDF';
                            @endphp
                            <form method="POST" action="{{ $isExcel ? route('super-admin.campus-admin.vpass.excel') : route('super-admin.campus-admin.vpass.pdf') }}" class="inline">
                                @csrf
                                <input type="hidden" name="campus_admin_filter" value="{{ request()->get('campus_admin_filter') }}">
                                @if(request()->has('form_title'))
                                    <input type="hidden" name="form_title" value="{{ request()->get('form_title') }}">
                                @endif
                                @if(request()->has('sg_code'))
                                    <input type="hidden" name="sg_code" value="{{ request()->get('sg_code') }}">
                                @endif
                                @if(request()->has('kra_title'))
                                    <input type="hidden" name="kra_title" value="{{ request()->get('kra_title') }}">
                                @endif
                                <button type="submit" class="inline-flex items-center px-4 py-2 {{ $isExcel ? 'bg-green-600 hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:ring-green-500' : 'bg-red-600 hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:ring-red-500' }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    {{ $buttonText }}
                                </button>
                            </form>
                            <a href="{{ route('super-admin.reports.qa-coordinator', request()->only('campus_admin_filter')) }}" 
                                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Close Preview
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Content -->
                <div class="p-6 bg-gray-50 overflow-auto" style="max-height: 80vh;">
                    <div class="bg-white p-6 shadow-sm">
                        @include('campus-admin.exports.vpass-format', [
                            'vpassData' => $vpassData,
                            'formTitle' => $formTitle ?? 'Performance Report'
                        ])
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-600">
                        This is a preview of your export. Click "{{ $buttonText }}" to generate and download the file.
                    </p>
                    <div class="flex space-x-3">
                        <a href="{{ route('super-admin.reports.qa-coordinator', request()->only('campus_admin_filter')) }}" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Close Preview
                        </a>
                        <form method="POST" action="{{ $isExcel ? route('super-admin.campus-admin.vpass.excel') : route('super-admin.campus-admin.vpass.pdf') }}" class="inline">
                            @csrf
                            <input type="hidden" name="campus_admin_filter" value="{{ request()->get('campus_admin_filter') }}">
                            @if(request()->has('form_title'))
                                <input type="hidden" name="form_title" value="{{ request()->get('form_title') }}">
                            @endif
                            @if(request()->has('sg_code'))
                                <input type="hidden" name="sg_code" value="{{ request()->get('sg_code') }}">
                            @endif
                            @if(request()->has('kra_title'))
                                <input type="hidden" name="kra_title" value="{{ request()->get('kra_title') }}">
                            @endif
                            <button type="submit" class="inline-flex items-center px-6 py-2 {{ $isExcel ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                {{ $buttonText }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

