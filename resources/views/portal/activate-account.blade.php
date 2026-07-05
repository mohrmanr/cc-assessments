<x-portal-layout>
    <div class="bg-white shadow-sm rounded-lg p-8 space-y-6">
        <div>
            <p class="text-sm text-indigo-600 font-semibold uppercase tracking-wide">Step 2 — Account activation</p>
            <h1 class="text-2xl font-bold mt-1">Welcome, {{ $submission->first_name }}</h1>
            <p class="mt-2 text-gray-600">Create a password for {{ $submission->email }}.</p>
        </div>

        <form method="POST" action="{{ route('activate.store', $invitation->token) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm password</label>
                <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Activate account
            </button>
        </form>
    </div>
</x-portal-layout>
