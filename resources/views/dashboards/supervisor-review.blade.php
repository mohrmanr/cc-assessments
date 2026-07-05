<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clinical Supervisor — Review Queue</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-semibold text-lg">Pending review ({{ $pending->count() }})</h3>
                @forelse ($pending as $item)
                    <div class="mt-4 border rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $item->first_name }} {{ $item->last_name }}</p>
                            <p class="text-sm text-gray-600">{{ $item->email }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ $item->auto_decision_reason }}</p>
                            @if ($item->safety_flag)
                                <span class="inline-block mt-2 text-xs font-semibold text-amber-800 bg-amber-100 px-2 py-1 rounded">Safety flag</span>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('supervisor.screenings.approve', $item) }}">
                                @csrf
                                <button class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('supervisor.screenings.decline', $item) }}">
                                @csrf
                                <button class="rounded-md bg-gray-200 px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-300">Decline</button>
                            </form>
                            <a href="{{ route('screening.result', $item) }}" class="rounded-md border px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">View result</a>
                        </div>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-gray-500">No cases awaiting review.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
