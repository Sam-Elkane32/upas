            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6 overflow-hidden">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Summary of Accomplishments</h3>
                                <p class="text-sm text-gray-600 mt-1">Preview and export comprehensive summary of all approved accomplishments</p>
                            </div>
                            <div class="flex space-x-3">
                                <a href="{{ route('super-admin.summary.export-pdf') }}" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Export Summary PDF
                                </a>
                                <a href="{{ route('super-admin.summary.export-excel') }}" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Export Summary Excel
                                </a>
                            </div>
                        </div>
                        
                        <!-- Preview Panel -->
                        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                            <div class="text-center py-8">
                                <svg class="mx-auto h-16 w-16 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-gray-900">Preview Summary</h3>
                                <p class="mt-2 text-sm text-gray-600">
                                    View a preview of your summary before exporting<br>
                                    All approved accomplishments from all campuses will be included
                                </p>
                                <div class="mt-6">
                                    <a href="{{ route('super-admin.summary.preview') }}" 
                                        target="_blank"
                                        class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <svg class="-ml-1 mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Preview Summary
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Info Section -->
                        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">About Summary of Accomplishments</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>This summary automatically compiles:</p>
                                        <ul class="list-disc list-inside mt-1 space-y-1">
                                            <li>All approved accomplishments from all campuses</li>
                                            <li>Strategic Goals (SG), Key Result Areas (KRA), and Key Performance Indicators (KPI)</li>
                                            <li>Comprehensive statistics and performance metrics</li>
                                            <li>Organized by SG, KRA, and KPI hierarchy</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <!-- End Tab Content: Summary of Accomplishments -->
