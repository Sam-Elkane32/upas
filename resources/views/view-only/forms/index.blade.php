<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Published Forms (Read-only)
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Open any form to see full structure, KPI targets, and roll-ups (same as planning view, without edit actions).
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('view-only.dashboard') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-blue-800">
                <p class="font-medium">Read-only access. Lists forms that are <strong>Published</strong> or have at least one <strong>published template</strong> (active in the workflow). Use <span class="font-semibold">Open</span> for full form detail.</p>
            </div>

            <div class="bg-white shadow-sm rounded-lg mb-6 p-4">
                <form method="GET" action="{{ route('view-only.forms.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    @if(!auth()->user()->restrictsViewOnlyToSingleCampus())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                        <select name="campus_code" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->code }}" {{ request('campus_code') == $campus->code ? 'selected' : '' }}>
                                    {{ $campus->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SG code</label>
                        <select name="sg_code" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach($sgCodes as $sg)
                                <option value="{{ $sg }}" {{ request('sg_code') == $sg ? 'selected' : '' }}>{{ $sg }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search title / template</label>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Form title or template code"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort</label>
                        <select name="sort_by" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="recent" {{ request('sort_by', 'recent') == 'recent' ? 'selected' : '' }}>Recently updated</option>
                            <option value="title" {{ request('sort_by') == 'title' ? 'selected' : '' }}>Title A–Z</option>
                            <option value="campus" {{ request('sort_by') == 'campus' ? 'selected' : '' }}>Campus</option>
                        </select>
                    </div>
                    <div class="md:col-span-5 flex justify-end gap-2">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">Apply</button>
                        <a href="{{ route('view-only.forms.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm font-medium">Clear</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Form</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SG</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($forms as $form)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-medium">{{ $form->form_title ?? 'Untitled' }}</div>
                                        @if($form->strategic_goal)
                                            <div class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $form->strategic_goal }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $form->campus->name ?? $form->campus_code ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $form->sg_code ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $form->template_code ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('forms.show', $form) }}"
                                           class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No forms match your filters (forms must be published or have a published template).</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($forms->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $forms->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
