<x-portal-layout>
    <x-slot name="title">Eligibility Screening</x-slot>

    <div class="bg-white shadow-sm rounded-lg p-8 space-y-6">
        <div>
            <p class="text-sm text-indigo-600 font-semibold uppercase tracking-wide">Step 1 of 4 — Screening</p>
            <h1 class="text-2xl font-bold mt-1">Eligibility screening</h1>
            <p class="mt-2 text-gray-600">
                Thank you for taking this step. Enter your contact details to continue to account activation.
            </p>
        </div>

        <form method="POST" action="{{ route('screening.store') }}" class="space-y-5">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', 'Alex') }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', 'Rivera') }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email', 'alex.rivera.demo'.time().'@example.com') }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <p class="text-xs text-gray-500 mt-1">Use a unique email for each demo run.</p>
            </div>

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex gap-3">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    Submit
                </button>
                <a href="{{ route('demo.script') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Demo guide
                </a>
            </div>
        </form>
    </div>
</x-portal-layout>
