<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Interviews</h2>
        <p class="mt-1 text-sm text-gray-500">Enter answers for assessments completed with a participant in an interview. Participants do not see these assessments on their dashboard.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                @if ($instruments->isEmpty())
                    <p class="p-6 text-sm text-gray-600">No active assessments are set to <strong>Staff interview</strong>. An admin can change "Completed by" in the assessment editor.</p>
                @else
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">User ID</th>
                                <th class="px-4 py-3">Participant</th>
                                <th class="px-4 py-3">Consent</th>
                                @foreach ($instruments as $instrument)
                                    <th class="px-4 py-3">{{ $instrument->version ?: $instrument->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($participants as $participant)
                                <tr>
                                    <td class="px-4 py-3 align-top font-mono font-semibold text-gray-900">{{ $participant->user_id }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-semibold text-gray-900">{{ $participant->user->name }}</p>
                                        <p class="text-gray-500">{{ $participant->user->email }}</p>
                                    </td>
                                    @php
                                        $consent = $participant->latestConsent;
                                        $consented = $participant->hasConsented();
                                    @endphp
                                    <td class="px-4 py-3 align-top">
                                        @if ($consented)
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Signed</span>
                                            <p class="mt-1 text-xs text-gray-500">{{ $consent->created_at->format('M j, Y') }}</p>
                                        @elseif ($consent)
                                            <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Declined</span>
                                            <p class="mt-1 text-xs text-gray-500">{{ $consent->created_at->format('M j, Y') }}</p>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">Not yet</span>
                                        @endif
                                    </td>
                                    @foreach ($instruments as $instrument)
                                        @php
                                            $result = $participant->assessmentResults->firstWhere('instrument_id', $instrument->id);
                                        @endphp
                                        <td class="px-4 py-3 align-top">
                                            @if ($result)
                                                <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Entered</span>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ $result->administered_at->format('M j, Y') }}
                                                    @if ($result->enteredBy)
                                                        by {{ $result->enteredBy->name }}
                                                    @endif
                                                </p>
                                            @elseif (! $consented)
                                                <span class="text-xs text-gray-500">Awaiting consent</span>
                                            @else
                                                <a href="{{ route('staff.interviews.show', [$instrument, $participant]) }}" class="inline-flex rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                                                    Enter answers
                                                </a>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + $instruments->count() }}" class="px-4 py-6 text-sm text-gray-500">No participants yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
