<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Participant Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <p class="text-gray-700">Welcome, {{ auth()->user()->name }}.</p>

                @if (! $baselineDone)
                    <div class="rounded-md bg-indigo-50 border border-indigo-100 p-4">
                        <p class="text-sm font-semibold text-indigo-900">Step 3 — Baseline surveys</p>
                        <p class="text-sm text-indigo-800 mt-1">Complete each baseline survey so your care team has a starting point.</p>
                    </div>
                @else
                    <p class="text-sm text-gray-600">Baseline surveys complete.</p>
                @endif

                <div class="grid md:grid-cols-3 gap-4">
                    @foreach ($baselineSurveys as $survey)
                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $survey['label'] }}</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ $survey['description'] }}</p>
                                </div>
                                @if ($survey['completed'])
                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Complete</span>
                                @else
                                    <span class="rounded-full bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-800">Pending</span>
                                @endif
                            </div>

                            @if ($survey['completed'])
                                <p class="mt-3 text-sm text-gray-700">
                                    Score: <strong>{{ $survey['result']->total_score }}</strong>
                                    @if ($survey['score_max'])
                                        / {{ $survey['score_max'] }}
                                    @endif
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Completed {{ $survey['result']->administered_at->format('M j, Y g:i A') }}
                                </p>
                            @else
                                <a href="{{ route($survey['route_name'], $survey['route_params'] ?? []) }}" class="mt-3 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                    Start {{ $survey['label'] }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($baselineDone)
                    @if ($latestResult)
                        <div class="rounded-md bg-gray-50 p-4 text-sm">
                            <p><strong>Latest score:</strong> {{ $latestResult->total_score }} ({{ $latestResult->instrument->name }})</p>
                            <p class="mt-1"><strong>Threshold met:</strong> {{ $latestResult->threshold_met ? 'Yes — clinician notified' : 'No' }}</p>
                            <p class="mt-1 text-gray-500">Administered {{ $latestResult->administered_at->format('M j, Y g:i A') }}</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
