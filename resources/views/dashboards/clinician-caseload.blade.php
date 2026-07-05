<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clinician Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-semibold text-lg">Pending treatment recommendations ({{ $recommendations->count() }})</h3>
                @forelse ($recommendations as $rec)
                    <div class="mt-4 border rounded-lg p-4">
                        <p class="font-medium">{{ $rec->participant->user->name }}</p>
                        <p class="text-sm text-gray-600">
                            {{ $rec->assessmentResult->instrument->name }} score: {{ $rec->assessmentResult->total_score }}
                            (threshold met)
                        </p>
                        <p class="text-sm text-gray-500">Recommended track: {{ $rec->recommendedTrack?->name ?? 'TBD' }}</p>
                        <form method="POST" action="{{ route('clinician.recommendations.confirm', $rec) }}" class="mt-3">
                            @csrf
                            <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                Confirm treatment recommendation
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-gray-500">No pending recommendations. Complete the participant PCL-5 demo first.</p>
                @endforelse
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-semibold text-lg">Assigned participants ({{ $participants->count() }})</h3>
                @forelse ($participants as $p)
                    <div class="mt-4 border rounded-lg p-4">
                        <p class="font-medium">{{ $p->user->name }}</p>
                        <p class="text-sm text-gray-600">{{ $p->user->email }}</p>
                        <p class="text-sm text-gray-500">Track: {{ $p->treatmentTrack?->name ?? 'Unassigned' }}</p>
                        @foreach ($p->assessmentResults as $result)
                            @php
                                $responses = $result->item_responses ?? [];
                                $fieldResponses = $responses['fields'] ?? [];
                                $itemResponses = $responses['items'] ?? $responses;
                                $itemsById = collect($result->instrument->items ?? [])->keyBy('id');
                            @endphp
                            <div class="mt-3 rounded-md bg-gray-50 p-3 text-sm">
                                <p>
                                    {{ $result->instrument->name }} ({{ $result->administration_type->value }}):
                                    <strong>{{ $result->total_score }}</strong>
                                    @if ($result->threshold_met)
                                        <span class="text-amber-700">— above threshold</span>
                                    @endif
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Completed {{ $result->administered_at->format('M j, Y g:i A') }}
                                </p>

                                @if (! empty($fieldResponses))
                                    <div class="mt-3">
                                        <p class="font-semibold text-gray-700">Reference information</p>
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
                                    <details class="mt-3">
                                        <summary class="cursor-pointer font-semibold text-gray-700">View item responses</summary>
                                        <dl class="mt-2 space-y-2">
                                            @foreach ($itemResponses as $itemId => $value)
                                                <div>
                                                    <dt class="text-gray-700">{{ $itemsById[$itemId]['text'] ?? $itemId }}</dt>
                                                    <dd class="text-gray-900">Response: {{ $value }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="mt-3 text-sm text-gray-500">No assigned participants yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
