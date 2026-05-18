<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Create New Submission
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Submit your Strategic Goal, KRA, KPI, and Accomplishment data
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $campus->name }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('campus-submissions.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <!-- Strategic Goal -->
                        <div>
                            <x-input-label for="strategic_goal" :value="__('Strategic Goal (SG)')" />
                            <x-text-input id="strategic_goal" 
                                class="mt-1 block w-full" 
                                type="text" 
                                name="strategic_goal" 
                                :value="old('strategic_goal')" 
                                required 
                                autofocus 
                                placeholder="Enter the strategic goal..."
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('strategic_goal')" />
                        </div>

                        <!-- Key Result Area -->
                        <div>
                            <x-input-label for="kra" :value="__('Key Result Area (KRA)')" />
                            <x-text-input id="kra" 
                                class="mt-1 block w-full" 
                                type="text" 
                                name="kra" 
                                :value="old('kra')" 
                                required 
                                placeholder="Enter the key result area..."
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('kra')" />
                        </div>

                        <!-- Key Performance Indicator -->
                        <div>
                            <x-input-label for="kpi" :value="__('Key Performance Indicator (KPI)')" />
                            <x-text-input id="kpi" 
                                class="mt-1 block w-full" 
                                type="text" 
                                name="kpi" 
                                :value="old('kpi')" 
                                required 
                                placeholder="Enter the key performance indicator..."
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('kpi')" />
                        </div>

                        <!-- Target and Actual Values -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="target_value" :value="__('Target Value')" />
                                <x-text-input id="target_value" 
                                    class="mt-1 block w-full" 
                                    type="number" 
                                    name="target_value" 
                                    :value="old('target_value')" 
                                    required 
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('target_value')" />
                            </div>

                            <div>
                                <x-input-label for="actual_value" :value="__('Actual Accomplishment')" />
                                <x-text-input id="actual_value" 
                                    class="mt-1 block w-full" 
                                    type="number" 
                                    name="actual_value" 
                                    :value="old('actual_value')" 
                                    required 
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('actual_value')" />
                                <p class="mt-1 text-sm text-gray-500">Must not exceed target value</p>
                            </div>
                        </div>

                        <!-- Achievement Percentage Display -->
                        <div id="achievement-display" class="hidden">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-blue-700">Achievement Percentage:</span>
                                    <span id="achievement-percentage" class="text-lg font-bold text-blue-900">0%</span>
                                </div>
                                <div class="mt-2 w-full bg-blue-200 rounded-full h-2">
                                    <div id="achievement-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Justification -->
                        <div>
                            <x-input-label for="justification" :value="__('Description / Justification (Optional)')" />
                            <textarea id="justification" 
                                name="justification" 
                                rows="4" 
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                placeholder="Provide additional details or justification for your accomplishment..."
                            >{{ old('justification') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('justification')" />
                        </div>

                        <!-- Supporting File Upload -->
                        <div>
                            <x-input-label for="supporting_file" :value="__('Supporting File (Optional)')" />
                            <input id="supporting_file" 
                                name="supporting_file" 
                                type="file" 
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p class="mt-1 text-sm text-gray-500">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max: 10MB)</p>
                            <x-input-error class="mt-2" :messages="$errors->get('supporting_file')" />
                        </div>

                        <!-- Google Drive Link -->
                        <div>
                            <x-input-label for="google_drive_link" :value="__('Google Drive Link (Optional)')" />
                            <x-text-input id="google_drive_link" 
                                class="mt-1 block w-full" 
                                type="url" 
                                name="google_drive_link" 
                                :value="old('google_drive_link')" 
                                placeholder="https://drive.google.com/..."
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('google_drive_link')" />
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="{{ route('campus-submissions.my-submissions') }}" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Submit for Approval') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Achievement Calculation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const targetInput = document.getElementById('target_value');
            const actualInput = document.getElementById('actual_value');
            const achievementDisplay = document.getElementById('achievement-display');
            const achievementPercentage = document.getElementById('achievement-percentage');
            const achievementBar = document.getElementById('achievement-bar');

            function calculateAchievement() {
                const target = parseFloat(targetInput.value) || 0;
                const actual = parseFloat(actualInput.value) || 0;
                
                if (target > 0) {
                    const percentage = Math.min(100, (actual / target) * 100);
                    achievementPercentage.textContent = percentage.toFixed(1) + '%';
                    achievementBar.style.width = percentage + '%';
                    achievementDisplay.classList.remove('hidden');
                } else {
                    achievementDisplay.classList.add('hidden');
                }
            }

            targetInput.addEventListener('input', calculateAchievement);
            actualInput.addEventListener('input', calculateAchievement);

            // Initial calculation if values are pre-filled
            calculateAchievement();
        });
    </script>
</x-app-layout>
