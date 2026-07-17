<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
    </x-slot>

    @php
        $defaultTab = 'assessments';
        if ($errors->any()) {
            $defaultTab = 'upload';
        } elseif (session('status') && str_contains(strtolower((string) session('status')), 'uploaded')) {
            $defaultTab = 'assessments';
        }
    @endphp

    <div class="py-12">
        <div
            class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6"
            x-data="{ tab: @js($defaultTab) }"
        >
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-800">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="border-b border-gray-200">
                <nav class="-mb-px flex flex-wrap gap-2" aria-label="Admin sections">
                    <button
                        type="button"
                        @click="tab = 'completed'"
                        :class="tab === 'completed'
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        class="whitespace-nowrap border-b-2 px-3 py-2 text-sm font-semibold"
                    >
                        Completed assessments
                        <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $completedAssessments->count() }}</span>
                    </button>
                    <button
                        type="button"
                        @click="tab = 'assessments'"
                        :class="tab === 'assessments'
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        class="whitespace-nowrap border-b-2 px-3 py-2 text-sm font-semibold"
                    >
                        Current assessments
                        <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $instruments->count() }}</span>
                    </button>
                    <button
                        type="button"
                        @click="tab = 'upload'"
                        :class="tab === 'upload'
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        class="whitespace-nowrap border-b-2 px-3 py-2 text-sm font-semibold"
                    >
                        Upload
                    </button>
                </nav>
            </div>

            <div x-show="tab === 'completed'" x-cloak class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-900">Completed Assessment Data</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Review submitted assessment results and download a CSV export for analysis or reporting.
                        </p>
                    </div>
                    <a href="{{ route('admin.assessments.completed.download') }}" class="inline-flex justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        Download CSV
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 pr-4">Participant</th>
                                <th class="py-2 pr-4">Assessment</th>
                                <th class="py-2 pr-4">Score</th>
                                <th class="py-2 pr-4">Threshold</th>
                                <th class="py-2 pr-4">Clinician</th>
                                <th class="py-2 pr-4">Completed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($completedAssessments as $result)
                                @php
                                    $responses = $result->item_responses ?? [];
                                    $fieldResponses = $responses['fields'] ?? [];
                                    $itemResponses = $responses['items'] ?? $responses;
                                    $itemsById = collect($result->instrument->items ?? [])->keyBy('id');
                                @endphp
                                <tr>
                                    <td class="py-2 pr-4">
                                        <div class="font-medium text-gray-900">{{ $result->participant->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $result->participant->user->email }}</div>
                                        <a href="{{ route('admin.participants.results', $result->participant) }}" class="mt-1 inline-block text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                                            View score chart
                                        </a>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <div class="text-gray-900">{{ $result->instrument->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $result->instrument->version }}</div>
                                    </td>
                                    <td class="py-2 pr-4 text-gray-900">{{ $result->total_score }}</td>
                                    <td class="py-2 pr-4">
                                        @if ($result->threshold_met)
                                            <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Met</span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">Not met</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $result->primaryClinician?->name ?? 'Unassigned' }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $result->administered_at->format('M j, Y g:i A') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="pb-4 pr-4 text-xs text-gray-600">
                                        <details>
                                            <summary class="cursor-pointer font-semibold text-gray-700">View submitted responses</summary>
                                            @if (! empty($fieldResponses))
                                                <div class="mt-2">
                                                    <p class="font-semibold">Reference information</p>
                                                    <dl class="mt-1 space-y-1">
                                                        @foreach ($fieldResponses as $field => $value)
                                                            <div>
                                                                <dt class="inline text-gray-500">{{ str($field)->replace('_', ' ')->title() }}:</dt>
                                                                <dd class="inline text-gray-900">{{ $value }}</dd>
                                                            </div>
                                                        @endforeach
                                                    </dl>
                                                </div>
                                            @endif
                                            @if (! empty($itemResponses))
                                                <div class="mt-2">
                                                    <p class="font-semibold">Item responses</p>
                                                    <dl class="mt-1 space-y-2">
                                                        @foreach ($itemResponses as $itemId => $value)
                                                            <div>
                                                                <dt class="text-gray-700">{{ $itemsById[$itemId]['text'] ?? $itemId }}</dt>
                                                                <dd class="text-gray-900">Response: {{ $value }}</dd>
                                                            </div>
                                                        @endforeach
                                                    </dl>
                                                </div>
                                            @endif
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-3 text-gray-500">No completed assessments yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="tab === 'assessments'" x-cloak class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg text-gray-900">Current Assessments</h3>
                <p class="mt-1 text-sm text-gray-600">Edit questions, descriptions, and answer scales for each assessment.</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 pr-4">Name</th>
                                <th class="py-2 pr-4">Slug</th>
                                <th class="py-2 pr-4">Version</th>
                                <th class="py-2 pr-4">Domain</th>
                                <th class="py-2 pr-4">Description</th>
                                <th class="py-2 pr-4">Items</th>
                                <th class="py-2 pr-4">Status</th>
                                <th class="py-2 pr-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($instruments as $instrument)
                                @php
                                    $instrumentDescription = $instrument->scoring_config['description']
                                        ?? config("portal.baseline_surveys.{$instrument->slug}.description")
                                        ?? '';
                                @endphp
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-900">{{ $instrument->name }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $instrument->slug }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $instrument->version }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $instrument->domain }}</td>
                                    <td class="py-2 pr-4 text-gray-600 max-w-xs">
                                        @if ($instrumentDescription !== '')
                                            <span title="{{ $instrumentDescription }}">{{ Str::limit($instrumentDescription, 80) }}</span>
                                        @else
                                            <span class="text-gray-400">Not set</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4 text-gray-600">{{ count($instrument->items ?? []) }}</td>
                                    <td class="py-2 pr-4">
                                        @if ($instrument->is_active)
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Active</span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4">
                                        <a href="{{ route('admin.instruments.edit', $instrument) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                                            Edit assessment
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-3 text-gray-500">No assessments configured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="tab === 'upload'" x-cloak class="space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-900">Upload New Assessment</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Upload a JSON assessment definition. The portal will validate it, save it as an active instrument, and show it on participant dashboards.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.assessments.upload') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label for="assessment_file" class="block text-sm font-medium text-gray-700">Assessment JSON file</label>
                            <input id="assessment_file" name="assessment_file" type="file" accept="application/json,.json" required class="mt-1 block w-full text-sm text-gray-700">
                        </div>
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Upload assessment
                        </button>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-lg text-gray-900">JSON Template</h3>
                    <pre class="overflow-auto rounded-md bg-gray-900 p-4 text-xs text-gray-100"><code>{
  "slug": "wellbeing-5",
  "name": "Well-Being Check",
  "version": "WB-5",
  "domain": "wellbeing",
  "description": "Complete the Well-Being Check.",
  "instructions": "Select the answer that best fits your experience.",
  "response_labels": {
    "0": "No",
    "1": "Yes"
  },
  "scoring_config": {
    "method": "sum",
    "threshold": 3,
    "direction": "above",
    "score_max": 5
  },
  "items": [
    {"id": "wb_1", "text": "Question one?"},
    {"id": "wb_2", "text": "Question two?"}
  ]
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
