<form action="{{ route('super-admin.templates.store-form') }}" method="POST" id="create-form-form">
    @csrf

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4" role="alert">
            <p class="text-sm font-semibold text-red-800">Please fix the following before creating the form:</p>
            <ul class="mt-2 list-disc list-inside text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <!-- Division Selection -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Division Selection
            </h3>
            <div>
                <label for="division" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Division <span class="text-red-500">*</span>
                </label>
                <select id="division" name="division" required 
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">Select Division</option>
                    <option value="OP" {{ old('division') == 'OP' ? 'selected' : '' }}>Office of the President (OP)</option>
                    <option value="OVPAFM" {{ old('division') == 'OVPAFM' ? 'selected' : '' }}>Office of the Vice President for Administration and Finance Management (OVPAFM)</option>
                    <option value="OVPASS" {{ old('division') == 'OVPASS' ? 'selected' : '' }}>Office of the Vice President for Academic and Student Services (OVPASS)</option>
                    <option value="OVPREI" {{ old('division') == 'OVPREI' ? 'selected' : '' }}>Office of the Vice President for Research, Extension & Innovation (OVPREI)</option>
                    <option value="OVPQA" {{ old('division') == 'OVPQA' ? 'selected' : '' }}>Office of the Vice President for Quality Assurance (OVPQA)</option>
                    <option value="OVPLIA" {{ old('division') == 'OVPLIA' ? 'selected' : '' }}>Office of the Vice President for Local & International Affairs (OVPLIA)</option>
                </select>
                @error('division')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Strategic Goal Selection -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
                Strategic Goal
            </h3>
            
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="sg_code" class="block text-sm font-medium text-gray-700 mb-2">
                        Strategic Goal <span class="text-red-500">*</span>
                    </label>
                    <select id="sg_code" name="sg_code" required 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Select Strategic Goal</option>
                        @foreach($strategicGoals as $code => $title)
                            <option value="{{ $code }}" {{ old('sg_code') == $code ? 'selected' : '' }}>
                                {{ $title }}
                            </option>
                        @endforeach
                    </select>
                    @error('sg_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <p class="mt-2 text-sm text-gray-500">Campus is assigned when you create or edit a template and select Planning Coordinator(s).</p>
        </div>
    </div>

    <!-- KRA and KPI Information -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                    KRA and KPI Information
                </h3>
            </div>
            
            <div id="kra-container">
                <!-- First KRA will be added by default -->
                <div class="kra-item mb-6 p-4 border-2 border-gray-300 rounded-lg bg-gray-50" data-kra-index="0">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-md font-semibold text-gray-800 kra-title">KRA 1.1</h4>
                        <button type="button" class="remove-kra text-red-600 hover:text-red-800" style="display: none;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            KRA Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="kra_titles[]" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="e.g., Curriculum and Instruction Enhancement"
                               value="{{ old('kra_titles.0') }}">
                        @error('kra_titles.0')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            KPI Numbers & Titles <span class="text-red-500">*</span>
                        </label>
                        <div class="kpi-container" data-kra-index="0">
                            <div class="kpi-item mb-3 p-3 border border-gray-200 rounded-md bg-white">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">KPI Title</label>
                                    <textarea name="kpi_titles[0][]" required rows="6"
                                              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm min-h-[120px] resize-y"
                                              placeholder="e.g., Number of reviewed, enhanced, and CHED-approved curriculum&#10;a. Industry-driven&#10;b. SDGs&#10;c. 21st century skills">{{ old('kpi_titles.0.0') }}</textarea>
                                </div>
                                <div class="grid grid-cols-12 gap-4 items-end mb-3">
                                    <div class="col-span-12 sm:col-span-2 min-w-0">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">KPI No.</label>
                                        <input type="text" name="kpi_numbers[0][]" required 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-mono kpi-number-input"
                                               placeholder="e.g., 1"
                                               value="{{ old('kpi_numbers.0.0', '1') }}">
                                    </div>
                                    <div class="col-span-6 sm:col-span-5 min-w-0">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Responsible Unit <span class="text-red-500">*</span>
                                        </label>
                                        <div class="responsible-unit-multi mt-1 relative">
                                            <input type="hidden" name="responsible_units[0][]" class="responsible-unit-value" value="{{ old('responsible_units.0.0') }}" required>
                                            <button type="button" class="responsible-unit-trigger w-full flex items-center justify-between gap-2 px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-left text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <span class="responsible-unit-trigger-text text-gray-500 truncate">Select Responsible Unit(s)...</span>
                                                <svg class="responsible-unit-chevron w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </button>
                                            <div class="responsible-unit-panel absolute left-0 right-0 mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-50 hidden">
                                                <div class="p-2 space-y-1 max-h-48 overflow-y-auto ru-options-list">
                                                    <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="CI" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">CI</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                                    <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="CED" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">CED</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                                    <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="Review Center" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">Review Center</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                                    <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="OUS ED" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">OUS ED</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                                    <div class="ru-custom-list"></div>
                                                </div>
                                                <div class="p-2 border-t border-gray-200 flex gap-2">
                                                    <input type="text" class="responsible-unit-custom flex-1 min-w-0 border border-gray-300 rounded px-2 py-1 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Add option">
                                                    <button type="button" class="responsible-unit-add-option px-2 py-1 text-sm border border-gray-300 rounded bg-white text-gray-700 hover:bg-gray-50">Add</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-span-6 sm:col-span-5 min-w-0">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Level (CL/UL) <span class="text-red-500">*</span>
                                        </label>
                                        <select name="kpi_levels[0][0]" required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-level-select">
                                            <option value="">Select Level</option>
                                            <option value="CL" {{ old('kpi_levels.0.0') == 'CL' ? 'selected' : '' }}>Campus Level (CL)</option>
                                            <option value="UL" {{ old('kpi_levels.0.0') == 'UL' ? 'selected' : '' }}>University Level (UL)</option>
                                            <option value="CL_UL" {{ old('kpi_levels.0.0') == 'CL_UL' ? 'selected' : '' }}>Both (CL / UL)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="border-t border-gray-200 pt-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-sm font-medium text-gray-700">Target Values</label>
                                        <label class="inline-flex items-center text-sm text-gray-600">
                                            <input type="checkbox" name="is_percentage_0" value="1" class="kpi-is-percentage rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" data-kpi-index="0">
                                            <span class="ml-2">Values in percentage (%)</span>
                                        </label>
                                    </div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xs text-gray-600">Total:</span>
                                        <input type="hidden" name="target_total_mode_0" value="average" class="kpi-total-mode-input">
                                        <button type="button" class="kpi-total-mode-btn px-2 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" data-mode="sum">Sum</button>
                                        <button type="button" class="kpi-total-mode-btn px-2 py-1 text-xs font-medium rounded border border-indigo-600 bg-indigo-600 text-white" data-mode="average">Average</button>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 target-values-grid">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Q1 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                            <input type="number" name="target_q1_0" min="0" step="0.01" max="100"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                                   data-kpi-index="0" data-quarter="1" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Q2 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                            <input type="number" name="target_q2_0" min="0" step="0.01" max="100"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                                   data-kpi-index="0" data-quarter="2" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Q3 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                            <input type="number" name="target_q3_0" min="0" step="0.01" max="100"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                                   data-kpi-index="0" data-quarter="3" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Q4 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                            <input type="number" name="target_q4_0" min="0" step="0.01" max="100"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                                   data-kpi-index="0" data-quarter="4" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Total <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                            <input type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm kpi-target-total-0"
                                                   readonly placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="kpi-entries-footer mt-1 flex flex-wrap items-center gap-2">
                            <button type="button" class="remove-kpi inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" style="display: none;" title="Remove last KPI in this KRA" aria-label="Delete KPI">Delete KPI</button>
                            <button type="button" class="add-kpi-btn inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-kra-index="0">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add KPI Title
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="button" id="add-kra" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add KRA Title
                </button>
            </div>
            
            @error('kra_titles')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('kpi_titles')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('kpi_levels')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('kpi_numbers')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>


    <!-- Submit Buttons -->
    <div class="flex justify-end space-x-4">
        <a href="{{ route('super-admin.templates.index', ['tab' => 'forms']) }}"
           class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
            Cancel
        </a>
        <button type="submit" 
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
            Create Form
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const CREATE_FORM_DRAFT_KEY = 'uaps_create_form_draft';
        const shouldRestoreDraft = @json($errors->any() || session('form_create_failed'));

        function saveCreateFormDraft() {
            const form = document.getElementById('create-form-form');
            if (!form) return;
            const fd = new FormData(form);
            const draft = {};
            for (const [key, value] of fd.entries()) {
                if (Object.prototype.hasOwnProperty.call(draft, key)) {
                    if (!Array.isArray(draft[key])) draft[key] = [draft[key]];
                    draft[key].push(value);
                } else {
                    draft[key] = value;
                }
            }
            try {
                sessionStorage.setItem(CREATE_FORM_DRAFT_KEY, JSON.stringify(draft));
            } catch (_) {}
        }

        function restoreCreateFormDraft() {
            let draft;
            try {
                draft = JSON.parse(sessionStorage.getItem(CREATE_FORM_DRAFT_KEY) || 'null');
            } catch (_) {
                return;
            }
            if (!draft || typeof draft !== 'object') return;
            const form = document.getElementById('create-form-form');
            if (!form) return;

            for (const [name, val] of Object.entries(draft)) {
                const values = Array.isArray(val) ? val : [val];
                const els = form.querySelectorAll('[name="' + name.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]');
                els.forEach((el, i) => {
                    const v = values[i] ?? values[0] ?? '';
                    if (el.type === 'checkbox') {
                        el.checked = (v === el.value || v === '1' || v === 'on');
                    } else {
                        el.value = v;
                    }
                });
            }

            const kraContainerEl = document.getElementById('kra-container');
            if (kraContainerEl) {
                kraContainerEl.querySelectorAll('.responsible-unit-multi').forEach(function(w) {
                    if (typeof initResponsibleUnitMulti === 'function') initResponsibleUnitMulti(w);
                    if (typeof syncResponsibleUnitToHidden === 'function') syncResponsibleUnitToHidden(w);
                });
            }
            if (typeof updateTargetValueIndices === 'function') updateTargetValueIndices();
            if (typeof initializeTargetValueListeners === 'function') initializeTargetValueListeners();
        }

        const kraContainer = document.getElementById('kra-container');
        const addKraBtn = document.getElementById('add-kra');
        
        // Get current KRA count from actual KRAs in container
        function getCurrentKraCount() {
            return kraContainer.querySelectorAll('.kra-item').length;
        }
        
        // Add KRA function
        if (addKraBtn) {
            addKraBtn.addEventListener('click', function() {
                const currentKraCount = getCurrentKraCount();
                const newKraIndex = currentKraCount; // This will be the index for the new KRA
                
                const kraItem = document.createElement('div');
                kraItem.className = 'kra-item mb-6 p-4 border-2 border-gray-300 rounded-lg bg-gray-50';
                kraItem.setAttribute('data-kra-index', newKraIndex);
                
                const sgCode = document.getElementById('sg_code')?.value || 'SG1';
                const sgNumber = sgCode.replace('SG', '') || '1';
                const kraNumber = `${sgNumber}.${currentKraCount + 1}`;
                kraItem.innerHTML = `
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-md font-semibold text-gray-800 kra-title">KRA ${kraNumber}</h4>
                        <button type="button" class="remove-kra text-red-600 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            KRA Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="kra_titles[]" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="e.g., Curriculum and Instruction Enhancement">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            KPI Numbers & Titles <span class="text-red-500">*</span>
                        </label>
                        <div class="kpi-container" data-kra-index="${newKraIndex}">
                            <div class="kpi-item mb-3 p-3 border border-gray-200 rounded-md bg-white">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">KPI Title</label>
                                    <textarea name="kpi_titles[${newKraIndex}][]" required rows="6"
                                              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm min-h-[120px] resize-y"
                                              placeholder="e.g., Number of reviewed, enhanced, and CHED-approved curriculum&#10;a. Industry-driven&#10;b. SDGs&#10;c. 21st century skills"></textarea>
                                </div>
                                <div class="grid grid-cols-12 gap-4 items-end mb-3">
                                    <div class="col-span-12 sm:col-span-2 min-w-0">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">KPI No.</label>
                                        <input type="text" name="kpi_numbers[${newKraIndex}][]" required 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-mono kpi-number-input"
                                               placeholder="e.g., 1"
                                               value="1">
                                    </div>
                                    <div class="col-span-6 sm:col-span-5 min-w-0">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Responsible Unit <span class="text-red-500">*</span></label>
                                        <div class="responsible-unit-multi mt-1 relative">
                                            <input type="hidden" name="responsible_units[${newKraIndex}][]" class="responsible-unit-value" value="" required>
                                            <button type="button" class="responsible-unit-trigger w-full flex items-center justify-between gap-2 px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-left text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <span class="responsible-unit-trigger-text text-gray-500 truncate">Select Responsible Unit(s)...</span>
                                                <svg class="responsible-unit-chevron w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </button>
                                            <div class="responsible-unit-panel absolute left-0 right-0 mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-50 hidden">
                                                <div class="p-2 space-y-1 max-h-48 overflow-y-auto ru-options-list">
                                                    <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="CI" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">CI</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                                    <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="CED" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">CED</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                                    <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="Review Center" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">Review Center</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                                    <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="OUS ED" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">OUS ED</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                                    <div class="ru-custom-list"></div>
                                                </div>
                                                <div class="p-2 border-t border-gray-200 flex gap-2">
                                                    <input type="text" class="responsible-unit-custom flex-1 min-w-0 border border-gray-300 rounded px-2 py-1 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Add option">
                                                    <button type="button" class="responsible-unit-add-option px-2 py-1 text-sm border border-gray-300 rounded bg-white text-gray-700 hover:bg-gray-50">Add</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-span-6 sm:col-span-5 min-w-0">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Level (CL/UL) <span class="text-red-500">*</span></label>
                                        <select name="kpi_levels[${newKraIndex}][0]" required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-level-select">
                                            <option value="">Select Level</option>
                                            <option value="CL">Campus Level (CL)</option>
                                            <option value="UL">University Level (UL)</option>
                                            <option value="CL_UL">Both (CL / UL)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="border-t border-gray-200 pt-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-sm font-medium text-gray-700">Target Values</label>
                                        <label class="inline-flex items-center text-sm text-gray-600">
                                            <input type="checkbox" name="is_percentage_0" value="1" class="kpi-is-percentage rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" data-kpi-index="0">
                                            <span class="ml-2">Values in percentage (%)</span>
                                        </label>
                                    </div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xs text-gray-600">Total:</span>
                                        <input type="hidden" name="target_total_mode_0" value="average" class="kpi-total-mode-input">
                                        <button type="button" class="kpi-total-mode-btn px-2 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" data-mode="sum">Sum</button>
                                        <button type="button" class="kpi-total-mode-btn px-2 py-1 text-xs font-medium rounded border border-indigo-600 bg-indigo-600 text-white" data-mode="average">Average</button>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 target-values-grid">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Q1 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                            <input type="number" name="target_q1_0" min="0" step="0.01" max="100"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                                   data-kpi-index="0" data-quarter="1" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Q2 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                            <input type="number" name="target_q2_0" min="0" step="0.01" max="100"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                                   data-kpi-index="0" data-quarter="2" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Q3 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                            <input type="number" name="target_q3_0" min="0" step="0.01" max="100"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                                   data-kpi-index="0" data-quarter="3" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Q4 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                            <input type="number" name="target_q4_0" min="0" step="0.01" max="100"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                                   data-kpi-index="0" data-quarter="4" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Total <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                            <input type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm kpi-target-total-0"
                                                   readonly placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="kpi-entries-footer mt-1 flex flex-wrap items-center gap-2">
                            <button type="button" class="remove-kpi inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" style="display: none;" title="Remove last KPI in this KRA" aria-label="Delete KPI">Delete KPI</button>
                            <button type="button" class="add-kpi-btn inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-kra-index="${newKraIndex}">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add KPI Title
                            </button>
                        </div>
                    </div>
                `;
                kraContainer.appendChild(kraItem);
                updateKraRemoveButtons();
                updateKraNumbers(); // Update all KRA numbers after adding
                attachKpiHandlers(kraItem);
                updateTargetValueIndices(); // Update target value indices after adding new KRA
                initializeTargetValueListeners(); // Initialize listeners for all KPIs
                
                // Scroll to the newly created KRA
                setTimeout(() => {
                    kraItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Focus on the KRA title input for better UX
                    const kraTitleInput = kraItem.querySelector('input[name="kra_titles[]"]');
                    if (kraTitleInput) {
                        kraTitleInput.focus();
                    }
                }, 100);
            });
        }
        
        // Remove KRA function
        if (kraContainer) {
            kraContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-kra')) {
                    e.target.closest('.kra-item').remove();
                    updateKraRemoveButtons();
                    updateKraNumbers();
                    updateTargetValueIndices(); // Update target value indices after removal
                }
            });
        }
        
        // Add KPI to specific KRA
        if (kraContainer) {
            kraContainer.addEventListener('click', function(e) {
                if (e.target.closest('.add-kpi-btn')) {
                    const btn = e.target.closest('.add-kpi-btn');
                    const kraIndex = btn.getAttribute('data-kra-index');
                    const footer = btn.closest('.kpi-entries-footer');
                    const kpiContainer = footer ? footer.previousElementSibling : btn.previousElementSibling;
                    const kpiItems = kpiContainer.querySelectorAll('.kpi-item');
                    const nextKpiNumber = kpiItems.length + 1; // Next sequential number
                    
                    // Calculate global KPI index for target values
                    let globalKpiIndex = 0;
                    const allKraItems = kraContainer.querySelectorAll('.kra-item');
                    allKraItems.forEach((kra, idx) => {
                        if (idx < parseInt(kraIndex)) {
                            const kpisInKra = kra.querySelectorAll('.kpi-item');
                            globalKpiIndex += kpisInKra.length;
                        }
                    });
                    // Add current KPI index
                    globalKpiIndex += kpiItems.length;
                    
                    const kpiItem = document.createElement('div');
                    kpiItem.className = 'kpi-item mb-3 p-3 border border-gray-200 rounded-md bg-white';
                    kpiItem.innerHTML = `
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">KPI Title</label>
                            <textarea name="kpi_titles[${kraIndex}][]" required rows="6"
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm min-h-[120px] resize-y"
                                      placeholder="e.g., Number of reviewed, enhanced, and CHED-approved curriculum&#10;a. Industry-driven&#10;b. SDGs&#10;c. 21st century skills"></textarea>
                        </div>
                        <div class="grid grid-cols-12 gap-4 items-end mb-3">
                            <div class="col-span-12 sm:col-span-2 min-w-0">
                                <label class="block text-sm font-medium text-gray-700 mb-2">KPI No.</label>
                                <input type="text" name="kpi_numbers[${kraIndex}][]" required 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-mono kpi-number-input"
                                       placeholder="e.g., ${nextKpiNumber}"
                                       value="${nextKpiNumber}">
                            </div>
                            <div class="col-span-6 sm:col-span-5 min-w-0">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Responsible Unit <span class="text-red-500">*</span></label>
                                <div class="responsible-unit-multi mt-1 relative">
                                    <input type="hidden" name="responsible_units[${kraIndex}][]" class="responsible-unit-value" value="" required>
                                    <button type="button" class="responsible-unit-trigger w-full flex items-center justify-between gap-2 px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-left text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <span class="responsible-unit-trigger-text text-gray-500 truncate">Select Responsible Unit(s)...</span>
                                        <svg class="responsible-unit-chevron w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div class="responsible-unit-panel absolute left-0 right-0 mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-50 hidden">
                                        <div class="p-2 space-y-1 max-h-48 overflow-y-auto ru-options-list">
                                            <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="CI" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">CI</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                            <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="CED" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">CED</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                            <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="Review Center" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">Review Center</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                            <div class="ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50"><label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="OUS ED" class="ru-option rounded border-gray-300 text-indigo-600"> <span class="flex-1">OUS ED</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button></div>
                                            <div class="ru-custom-list"></div>
                                        </div>
                                        <div class="p-2 border-t border-gray-200 flex gap-2">
                                            <input type="text" class="responsible-unit-custom flex-1 min-w-0 border border-gray-300 rounded px-2 py-1 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Add option">
                                            <button type="button" class="responsible-unit-add-option px-2 py-1 text-sm border border-gray-300 rounded bg-white text-gray-700 hover:bg-gray-50">Add</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-5 min-w-0">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Level (CL/UL) <span class="text-red-500">*</span></label>
                                <select name="kpi_levels[${kraIndex}][${kpiItems.length}]" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-level-select">
                                    <option value="">Select Level</option>
                                    <option value="CL">Campus Level (CL)</option>
                                    <option value="UL">University Level (UL)</option>
                                    <option value="CL_UL">Both (CL / UL)</option>
                                </select>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Target Values</label>
                                <label class="inline-flex items-center text-sm text-gray-600">
                                    <input type="checkbox" name="is_percentage_${globalKpiIndex}" value="1" class="kpi-is-percentage rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" data-kpi-index="${globalKpiIndex}">
                                    <span class="ml-2">Values in percentage (%)</span>
                                </label>
                            </div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs text-gray-600">Total:</span>
                                <input type="hidden" name="target_total_mode_${globalKpiIndex}" value="average" class="kpi-total-mode-input">
                                <button type="button" class="kpi-total-mode-btn px-2 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" data-mode="sum">Sum</button>
                                <button type="button" class="kpi-total-mode-btn px-2 py-1 text-xs font-medium rounded border border-indigo-600 bg-indigo-600 text-white" data-mode="average">Average</button>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 target-values-grid">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Q1 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                    <input type="number" name="target_q1_${globalKpiIndex}" min="0" step="0.01" max="100"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                           data-kpi-index="${globalKpiIndex}" data-quarter="1" placeholder="0">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Q2 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                    <input type="number" name="target_q2_${globalKpiIndex}" min="0" step="0.01" max="100"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                           data-kpi-index="${globalKpiIndex}" data-quarter="2" placeholder="0">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Q3 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                    <input type="number" name="target_q3_${globalKpiIndex}" min="0" step="0.01" max="100"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                           data-kpi-index="${globalKpiIndex}" data-quarter="3" placeholder="0">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Q4 <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                    <input type="number" name="target_q4_${globalKpiIndex}" min="0" step="0.01" max="100"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm kpi-target-input"
                                           data-kpi-index="${globalKpiIndex}" data-quarter="4" placeholder="0">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Total <span class="target-pct-suffix text-gray-500" style="display:none">(%)</span></label>
                                    <input type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm kpi-target-total-${globalKpiIndex}"
                                           readonly placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    `;
                    kpiContainer.appendChild(kpiItem);
                    renumberKpis(kpiContainer); // Renumber all KPIs sequentially
                    updateKpiRemoveButtons(kpiContainer);
                    updateTargetValueIndices(); // Update all target value indices
                    attachTargetValueListeners(kpiItem); // Attach listeners to new KPI's target inputs
                }
            });
        }
        
        // Remove KPI function (footer "Delete KPI" removes the last KPI in that KRA)
        if (kraContainer) {
            kraContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-kpi')) {
                    const delBtn = e.target.closest('.remove-kpi');
                    const footer = delBtn.closest('.kpi-entries-footer');
                    const kpiContainer = footer ? footer.previousElementSibling : null;
                    if (!kpiContainer || !kpiContainer.classList.contains('kpi-container')) return;
                    const kpiItems = kpiContainer.querySelectorAll('.kpi-item');
                    if (kpiItems.length <= 1) return;
                    kpiItems[kpiItems.length - 1].remove();
                    renumberKpis(kpiContainer);
                    updateKpiRemoveButtons(kpiContainer);
                    updateTargetValueIndices();
                }
            });
        }
        
        // Attach KPI handlers to a KRA item
        function attachKpiHandlers(kraItem) {
            const addKpiBtn = kraItem.querySelector('.add-kpi-btn');
            const kpiContainer = kraItem.querySelector('.kpi-container');
            if (addKpiBtn && kpiContainer) {
                updateKpiRemoveButtons(kpiContainer);
            }
        }
        
        // Update KRA remove buttons visibility
        function updateKraRemoveButtons() {
            const kraItems = kraContainer.querySelectorAll('.kra-item');
            kraItems.forEach((item, index) => {
                const removeBtn = item.querySelector('.remove-kra');
                if (removeBtn) {
                    removeBtn.style.display = kraItems.length > 1 ? 'block' : 'none';
                }
            });
        }
        
        // Update KPI delete button visibility (footer "Delete KPI" when more than one KPI)
        function updateKpiRemoveButtons(kpiContainer) {
            const kpiItems = kpiContainer.querySelectorAll('.kpi-item');
            const footer = kpiContainer.nextElementSibling;
            const removeBtn = footer && footer.classList.contains('kpi-entries-footer')
                ? footer.querySelector('.remove-kpi')
                : null;
            if (removeBtn) {
                removeBtn.style.display = kpiItems.length > 1 ? 'inline-flex' : 'none';
            }
        }
        
        // Renumber KPIs sequentially within a KRA
        function renumberKpis(kpiContainer) {
            const kpiItems = kpiContainer.querySelectorAll('.kpi-item');
            const kraIndex = kpiContainer.getAttribute('data-kra-index') || '0';
            kpiItems.forEach((item, index) => {
                const kpiNumberInput = item.querySelector('input[name^="kpi_numbers"]');
                if (kpiNumberInput) {
                    kpiNumberInput.value = (index + 1).toString();
                }
                item.querySelectorAll('select[name^="kpi_levels["]').forEach((select) => {
                    select.name = `kpi_levels[${kraIndex}][${index}]`;
                });
            });
        }
        
        // Update KRA numbers based on Strategic Goal and reindex data-kra-index
        function updateKraNumbers() {
            const sgCode = document.getElementById('sg_code')?.value || 'SG1';
            const sgNumber = sgCode.replace('SG', '') || '1';
            const kraItems = kraContainer.querySelectorAll('.kra-item');
            
            kraItems.forEach((item, index) => {
                // Update the data-kra-index to match the new position
                item.setAttribute('data-kra-index', index);
                
                // Update the title display
                const title = item.querySelector('.kra-title');
                if (title) {
                    title.textContent = `KRA ${sgNumber}.${index + 1}`;
                }
                
                // Update all input names to use the new index
                const kpiContainer = item.querySelector('.kpi-container');
                if (kpiContainer) {
                    kpiContainer.setAttribute('data-kra-index', index);
                    
                    // Update all KPI input names
                    kpiContainer.querySelectorAll('input[name^="kpi_numbers["]').forEach(input => {
                        const oldName = input.name;
                        const match = oldName.match(/kpi_numbers\[(\d+)\]/);
                        if (match) {
                            input.name = oldName.replace(/kpi_numbers\[\d+\]/, `kpi_numbers[${index}]`);
                        }
                    });
                    
                    kpiContainer.querySelectorAll('input[name^="kpi_titles["]').forEach(input => {
                        const oldName = input.name;
                        const match = oldName.match(/kpi_titles\[(\d+)\]/);
                        if (match) {
                            input.name = oldName.replace(/kpi_titles\[\d+\]/, `kpi_titles[${index}]`);
                        }
                    });
                    
                    kpiContainer.querySelectorAll('input[name^="responsible_units["]').forEach(input => {
                        const oldName = input.name;
                        const match = oldName.match(/responsible_units\[(\d+)\]/);
                        if (match) {
                            input.name = oldName.replace(/responsible_units\[\d+\]/, `responsible_units[${index}]`);
                        }
                    });

                    kpiContainer.querySelectorAll('select[name^="kpi_levels["]').forEach((select) => {
                        select.name = select.name.replace(/kpi_levels\[\d+\]/, `kpi_levels[${index}]`);
                    });
                    
                    // Update add-kpi-btn data attribute
                    const addKpiBtn = item.querySelector('.add-kpi-btn');
                    if (addKpiBtn) {
                        addKpiBtn.setAttribute('data-kra-index', index);
                    }
                }
            });
        }
        
        // Strategic Goal change listener
        const sgCodeSelect = document.getElementById('sg_code');
        if (sgCodeSelect) {
            sgCodeSelect.addEventListener('change', function() {
                updateKraNumbers();
            });
        }
        
        // Initialize
        updateKraRemoveButtons();
        updateKraNumbers(); // Initialize KRA numbering
        const firstKpiContainer = kraContainer.querySelector('.kpi-container');
        if (firstKpiContainer) {
            updateKpiRemoveButtons(firstKpiContainer);
        }
        
        // Update target value indices for all KPIs
        function updateTargetValueIndices() {
            let globalKpiIndex = 0;
            const kraItems = kraContainer.querySelectorAll('.kra-item');
            
            kraItems.forEach((kraItem) => {
                const kpiItems = kraItem.querySelectorAll('.kpi-item');
                kpiItems.forEach((kpiItem) => {
                    // Update target input names and data attributes
                    const targetInputs = kpiItem.querySelectorAll('.kpi-target-input');
                    targetInputs.forEach(input => {
                        const quarter = input.dataset.quarter;
                        const newName = `target_q${quarter}_${globalKpiIndex}`;
                        input.name = newName;
                        input.setAttribute('data-kpi-index', globalKpiIndex);
                    });
                    
                    // Update is_percentage checkbox name and data attribute
                    const pctCheckbox = kpiItem.querySelector('.kpi-is-percentage');
                    if (pctCheckbox) {
                        pctCheckbox.name = `is_percentage_${globalKpiIndex}`;
                        pctCheckbox.setAttribute('data-kpi-index', globalKpiIndex);
                    }
                    // Update total mode hidden input name
                    const modeInput = kpiItem.querySelector('.kpi-total-mode-input');
                    if (modeInput) modeInput.name = `target_total_mode_${globalKpiIndex}`;
                    // Update total mode button active state from current value
                    const modeVal = modeInput ? modeInput.value : 'average';
                    kpiItem.querySelectorAll('.kpi-total-mode-btn').forEach(btn => {
                        const isActive = btn.getAttribute('data-mode') === modeVal;
                        btn.classList.toggle('border-indigo-600', isActive);
                        btn.classList.toggle('bg-indigo-600', isActive);
                        btn.classList.toggle('text-white', isActive);
                        btn.classList.toggle('border-gray-300', !isActive);
                        btn.classList.toggle('bg-white', !isActive);
                        btn.classList.toggle('text-gray-700', !isActive);
                    });
                    // Update total input class
                    const totalInput = kpiItem.querySelector('[class*="kpi-target-total"]');
                    if (totalInput) {
                        totalInput.className = `mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm kpi-target-total-${globalKpiIndex}`;
                    }
                    
                    globalKpiIndex++;
                });
            });
        }
        
        // Attach target value listeners to a KPI item
        function attachTargetValueListeners(kpiItem) {
            const targetInputs = kpiItem.querySelectorAll('.kpi-target-input');
            targetInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const kpiIndex = this.dataset.kpiIndex;
                    calculateKpiTotalInItem(kpiItem, kpiIndex);
                });
            });
            // Toggle (%) suffix and recalc total when percentage checkbox changes
                const pctCheckbox = kpiItem.querySelector('.kpi-is-percentage');
                if (pctCheckbox) {
                    pctCheckbox.addEventListener('change', function() {
                        const suffixes = kpiItem.querySelectorAll('.target-pct-suffix');
                        suffixes.forEach(el => { el.style.display = this.checked ? 'inline' : 'none'; });
                        const kpiIndex = this.dataset.kpiIndex;
                        calculateKpiTotalInItem(kpiItem, kpiIndex);
                    });
                }
                // Sum/Average button clicks
                kpiItem.querySelectorAll('.kpi-total-mode-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const mode = this.getAttribute('data-mode');
                        const modeInput = kpiItem.querySelector('.kpi-total-mode-input');
                        if (modeInput) modeInput.value = mode;
                        kpiItem.querySelectorAll('.kpi-total-mode-btn').forEach(b => {
                            const isActive = b.getAttribute('data-mode') === mode;
                            b.classList.toggle('border-indigo-600', isActive);
                            b.classList.toggle('bg-indigo-600', isActive);
                            b.classList.toggle('text-white', isActive);
                            b.classList.toggle('border-gray-300', !isActive);
                            b.classList.toggle('bg-white', !isActive);
                            b.classList.toggle('text-gray-700', !isActive);
                        });
                        const kpiIndex = kpiItem.querySelector('.kpi-target-input')?.dataset?.kpiIndex;
                        if (kpiIndex !== undefined) calculateKpiTotalInItem(kpiItem, kpiIndex);
                    });
                });
            }
        
        // Calculate total using selected mode (Sum or Average)
        function calculateKpiTotalInItem(kpiItem, kpiIndex) {
            const q1Input = kpiItem.querySelector(`input[data-quarter="1"]`);
            const q2Input = kpiItem.querySelector(`input[data-quarter="2"]`);
            const q3Input = kpiItem.querySelector(`input[data-quarter="3"]`);
            const q4Input = kpiItem.querySelector(`input[data-quarter="4"]`);
            
            const q1 = q1Input && q1Input.value !== '' ? parseFloat(q1Input.value) : null;
            const q2 = q2Input && q2Input.value !== '' ? parseFloat(q2Input.value) : null;
            const q3 = q3Input && q3Input.value !== '' ? parseFloat(q3Input.value) : null;
            const q4 = q4Input && q4Input.value !== '' ? parseFloat(q4Input.value) : null;
            const all = [(q1 ?? 0), (q2 ?? 0), (q3 ?? 0), (q4 ?? 0)];
            const values = [q1, q2, q3, q4].filter(v => v !== null && v !== '' && Number(v) > 0);
            const isPct = kpiItem.querySelector('.kpi-is-percentage') && kpiItem.querySelector('.kpi-is-percentage').checked;
            const modeInput = kpiItem.querySelector('.kpi-total-mode-input');
            const mode = modeInput ? modeInput.value : 'average';
            let total;
            if (mode === 'sum') {
                if (isPct && values.length > 0) {
                    total = values.reduce((a, b) => a + b, 0);
                } else {
                    total = all.reduce((a, b) => a + b, 0);
                }
            } else {
                if (isPct && values.length > 0) {
                    total = values.reduce((a, b) => a + b, 0) / values.length;
                } else {
                    const sum = all.reduce((a, b) => a + b, 0);
                    total = sum > 0 ? sum / 4 : 0;
                }
            }
            const totalInput = kpiItem.querySelector(`.kpi-target-total-${kpiIndex}`);
            if (totalInput) {
                if (total > 0 || (isPct && values.length > 0)) {
                    totalInput.value = total.toFixed(2);
                } else {
                    totalInput.value = '';
                }
            }
        }
        
        // Initialize target value listeners for existing KPIs
        function initializeTargetValueListeners() {
            const allKpiItems = kraContainer.querySelectorAll('.kpi-item');
            allKpiItems.forEach(kpiItem => {
                attachTargetValueListeners(kpiItem);
            });
        }
        
        // Initialize on page load
        updateTargetValueIndices();
        initializeTargetValueListeners();
        
        // Strategic Goal change listener
        if (sgCodeSelect) {
            sgCodeSelect.addEventListener('change', function() {
                const newSgCode = this.value;
                if (newSgCode !== currentSgCode) {
                    currentSgCode = newSgCode;
                    resetKpiNumbers();
                }
            });
        }
        
        // Reset KPI numbers when SG changes
        function resetKpiNumbers() {
            const kpiNumberInputs = kpiContainer.querySelectorAll('input[name="kpi_numbers[]"]');
            kpiNumberInputs.forEach((input, index) => {
                input.value = index + 1;
            });
            // Clear any validation errors
            clearKpiNumberErrors();
        }
        
        // Add KPI number validation
        function addKpiNumberValidation() {
            const kpiNumberInputs = kpiContainer.querySelectorAll('.kpi-number-input');
            kpiNumberInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateKpiNumber(this);
                });
            });
        }
        
        // Validate KPI number for duplicates
        function validateKpiNumber(input) {
            const value = input.value.trim();
            const allKpiNumbers = Array.from(kpiContainer.querySelectorAll('.kpi-number-input'))
                .map(inp => inp.value.trim())
                .filter(val => val !== '');
            
            const duplicates = allKpiNumbers.filter((val, index) => 
                allKpiNumbers.indexOf(val) !== index
            );
            
            const errorDiv = input.parentNode.querySelector('.kpi-number-error');
            
            if (duplicates.includes(value) && value !== '') {
                input.classList.add('border-red-500');
                input.classList.remove('border-gray-300');
                errorDiv.textContent = 'KPI Number already exists';
                errorDiv.style.display = 'block';
            } else {
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
                errorDiv.style.display = 'none';
            }
        }
        
        // Clear all KPI number errors
        function clearKpiNumberErrors() {
            const errorDivs = kpiContainer.querySelectorAll('.kpi-number-error');
            const inputs = kpiContainer.querySelectorAll('.kpi-number-input');
            
            errorDivs.forEach(div => {
                div.style.display = 'none';
            });
            
            inputs.forEach(input => {
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
            });
        }
        
        // Validate CL/UL dropdown on form submit
        const createFormForm = document.getElementById('create-form-form');
        if (createFormForm) {
            createFormForm.addEventListener('submit', function(e) {
                saveCreateFormDraft();
                const allKpiItems = kraContainer.querySelectorAll('.kpi-item');
                let hasError = false;
                
                allKpiItems.forEach((kpiItem, index) => {
                    const levelSelect = kpiItem.querySelector('.kpi-level-select');
                    if (levelSelect && !levelSelect.value) {
                        hasError = true;
                        levelSelect.classList.add('border-red-500');
                        levelSelect.classList.remove('border-gray-300');
                    } else if (levelSelect) {
                        levelSelect.classList.remove('border-red-500');
                        levelSelect.classList.add('border-gray-300');
                    }
                });
                
                if (hasError) {
                    e.preventDefault();
                    window.showAlert({ title: 'Notice', message: 'Please select a level (CL or UL) for each KPI.' });
                    return false;
                }
                // Ensure Responsible Unit multi-select has at least one selection
                const ruInputs = kraContainer.querySelectorAll('.responsible-unit-value');
                for (const inp of ruInputs) {
                    if (!inp.value || !inp.value.trim()) {
                        e.preventDefault();
                        window.showAlert({ title: 'Notice', message: 'Please select at least one Responsible Unit for each KPI.' });
                        return false;
                    }
                }
            });
        }
        
        // Responsible Unit dropdown: init from value, sync on change, add custom option
        const RU_STORAGE_KEY = 'uaps_responsible_unit_custom_options';
        function getStoredResponsibleUnitOptions() {
            try {
                const raw = localStorage.getItem(RU_STORAGE_KEY);
                return raw ? JSON.parse(raw) : [];
            } catch (_) { return []; }
        }
        function addStoredResponsibleUnitOption(val) {
            const list = getStoredResponsibleUnitOptions();
            if (!val || list.includes(val)) return;
            list.push(val);
            try { localStorage.setItem(RU_STORAGE_KEY, JSON.stringify(list)); } catch (_) {}
        }
        function removeStoredResponsibleUnitOption(val) {
            const list = getStoredResponsibleUnitOptions().filter(v => v !== val);
            try { localStorage.setItem(RU_STORAGE_KEY, JSON.stringify(list)); } catch (_) {}
        }
        function ruOptionRowHtml(escapedVal, labelText, checked = false) {
            const c = checked ? ' checked' : '';
            return `<label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0"><input type="checkbox" value="${escapedVal}" class="ru-option rounded border-gray-300 text-indigo-600"${c}> <span class="flex-1">${labelText}</span></label><button type="button" class="ru-option-remove opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-red-100 text-gray-500 hover:text-red-600 text-lg leading-none" aria-label="Remove">×</button>`;
        }
        // Tracks selection order per wrapper so display matches click order
        const ruSelectionOrder = new WeakMap();
        function parseResponsibleUnitValue(val) {
            if (!val || !String(val).trim()) return [];
            return String(val).split(/[,/]+/).map(s => s.trim()).filter(Boolean);
        }
        function syncResponsibleUnitToHidden(wrapper) {
            const hidden = wrapper.querySelector('.responsible-unit-value');
            const triggerText = wrapper.querySelector('.responsible-unit-trigger-text');
            if (!hidden) return;
            const ordered = ruSelectionOrder.get(wrapper) || [];
            // Filter to only currently-checked items (guards against desyncs)
            const checked = new Set(Array.from(wrapper.querySelectorAll('.ru-option:checked')).map(o => o.value));
            const selected = ordered.filter(v => checked.has(v));
            ruSelectionOrder.set(wrapper, selected);
            hidden.value = selected.join(', ');
            if (triggerText) triggerText.textContent = selected.length ? selected.join(', ') : 'Select Responsible Unit(s)...';
            if (triggerText) triggerText.classList.toggle('text-gray-500', selected.length === 0);
            if (triggerText) triggerText.classList.toggle('text-gray-900', selected.length > 0);
        }
        function initResponsibleUnitMulti(wrapper) {
            const hidden = wrapper.querySelector('.responsible-unit-value');
            const customList = wrapper.querySelector('.ru-custom-list');
            if (!hidden || !customList) return;
            const defaultVals = ['CI', 'CED', 'Review Center', 'OUS ED'];
            const parts = parseResponsibleUnitValue(hidden.value);
            // Initialise selection order from the stored value (preserves saved order)
            ruSelectionOrder.set(wrapper, []);
            parts.forEach(partRaw => {
                // Migrate legacy "OUS" value to renamed "OUS ED"
                const part = (partRaw === 'OUS') ? 'OUS ED' : partRaw;
                const escapedPart = part.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const existing = wrapper.querySelector(`.ru-option[value="${escapedPart}"]`);
                if (existing) {
                    existing.checked = true;
                    ruSelectionOrder.get(wrapper).push(part);
                } else if (defaultVals.indexOf(part) === -1) {
                    const div = document.createElement('div');
                    div.className = 'ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50';
                    div.innerHTML = ruOptionRowHtml(escapedPart, escapedPart, false);
                    customList.appendChild(div);
                    ruSelectionOrder.get(wrapper).push(part);
                }
            });
            // Restore custom options from localStorage so they persist after refresh
            getStoredResponsibleUnitOptions().forEach(part => {
                if (!part || defaultVals.includes(part)) return;
                const escapedPart = part.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                if (wrapper.querySelector(`.ru-option[value="${escapedPart}"]`)) return;
                const div = document.createElement('div');
                div.className = 'ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50';
                div.innerHTML = ruOptionRowHtml(escapedPart, escapedPart, false);
                customList.appendChild(div);
            });
            syncResponsibleUnitToHidden(wrapper);
        }
        function attachResponsibleUnitMultiListeners(wrapper) {
            const panel = wrapper.querySelector('.responsible-unit-panel');
            const addBtn = wrapper.querySelector('.responsible-unit-add-option');
            const customInput = wrapper.querySelector('.responsible-unit-custom');
            if (panel) panel.addEventListener('click', function(e) {
                if (e.target.closest('.ru-option-remove')) return;
                e.stopPropagation();
            });
            wrapper.addEventListener('change', function(e) {
                if (e.target && e.target.classList.contains('ru-option')) {
                    const order = ruSelectionOrder.get(wrapper) || [];
                    const val = e.target.value;
                    if (e.target.checked) {
                        if (!order.includes(val)) order.push(val);
                    } else {
                        const idx = order.indexOf(val);
                        if (idx !== -1) order.splice(idx, 1);
                    }
                    ruSelectionOrder.set(wrapper, order);
                    syncResponsibleUnitToHidden(wrapper);
                }
            });
            const customList = wrapper.querySelector('.ru-custom-list');
            if (addBtn && customInput) {
                addBtn.addEventListener('click', function() {
                    const val = customInput.value.trim();
                    if (!val) return;
                    addStoredResponsibleUnitOption(val);
                    const escapedVal = val.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    const exists = wrapper.querySelector(`.ru-option[value="${escapedVal}"]`);
                    if (!exists) {
                        const div = document.createElement('div');
                        div.className = 'ru-option-row flex items-center gap-2 rounded px-2 py-1 group hover:bg-gray-50';
                        div.innerHTML = ruOptionRowHtml(escapedVal, escapedVal, true);
                        customList.appendChild(div);
                    } else {
                        const opt = wrapper.querySelector(`.ru-option[value="${escapedVal}"]`);
                        if (opt) opt.checked = true;
                    }
                    const order = ruSelectionOrder.get(wrapper) || [];
                    if (!order.includes(val)) order.push(val);
                    ruSelectionOrder.set(wrapper, order);
                    customInput.value = '';
                    syncResponsibleUnitToHidden(wrapper);
                });
            }
        }
        // Delegated trigger click so open/close works even after adding custom options
        kraContainer.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.ru-option-remove');
            if (removeBtn) {
                e.preventDefault();
                e.stopPropagation();
                const row = removeBtn.closest('.ru-option-row');
                const wrapper = removeBtn.closest('.responsible-unit-multi');
                if (!row || !wrapper) return;
                const checkbox = row.querySelector('.ru-option');
                const value = checkbox ? checkbox.value : '';
                row.remove();
                const defaultVals = ['CI', 'CED', 'Review Center', 'OUS ED'];
                const rawVal = value.replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
                if (rawVal && !defaultVals.includes(rawVal)) removeStoredResponsibleUnitOption(rawVal);
                syncResponsibleUnitToHidden(wrapper);
                return;
            }
            const trigger = e.target.closest('.responsible-unit-trigger');
            if (!trigger) return;
            const wrapper = trigger.closest('.responsible-unit-multi');
            if (!wrapper) return;
            e.stopPropagation();
            const panel = wrapper.querySelector('.responsible-unit-panel');
            if (!panel) return;
            const isOpen = !panel.classList.contains('hidden');
            document.querySelectorAll('.responsible-unit-panel').forEach(p => p.classList.add('hidden'));
            if (!isOpen) panel.classList.remove('hidden');
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.responsible-unit-multi')) {
                document.querySelectorAll('.responsible-unit-panel').forEach(p => p.classList.add('hidden'));
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.responsible-unit-panel').forEach(p => p.classList.add('hidden'));
            }
        });
        kraContainer.querySelectorAll('.responsible-unit-multi').forEach(w => {
            initResponsibleUnitMulti(w);
            attachResponsibleUnitMultiListeners(w);
        });
        const ruObserver = new MutationObserver(function() {
            kraContainer.querySelectorAll('.responsible-unit-multi').forEach(w => {
                if (!w.dataset.ruInitialized) {
                    w.dataset.ruInitialized = '1';
                    initResponsibleUnitMulti(w);
                    attachResponsibleUnitMultiListeners(w);
                }
            });
        });
        ruObserver.observe(kraContainer, { childList: true, subtree: true });

        if (shouldRestoreDraft) {
            restoreCreateFormDraft();
        }
    });
</script>

