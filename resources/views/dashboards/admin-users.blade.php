<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Accounts and roles</h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Admin dashboard</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-semibold text-lg text-gray-900">Create Learner</h3>
                <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 grid gap-4 sm:grid-cols-4">
                    @csrf
                    <input name="name" required placeholder="Name" class="rounded-md border-gray-300 text-sm">
                    <input name="email" type="email" required placeholder="Email" class="rounded-md border-gray-300 text-sm">
                    <input name="password" type="password" required minlength="8" placeholder="Password" class="rounded-md border-gray-300 text-sm">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Create</button>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg w-full overflow-x-auto">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                    <p class="text-sm text-gray-600">User ID is the permanent number for each account. Use it to de-identify exported data.</p>
                    <a href="{{ route('admin.users.download') }}" class="inline-flex justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        Download ID list (CSV)
                    </a>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">User ID</th>
                            <th class="px-4 py-3">Account</th>
                            <th class="px-4 py-3">Started therapy</th>
                            <th class="px-4 py-3">Roles</th>
                            <th class="px-4 py-3">Course access</th>
                            <th class="sticky right-0 z-10 bg-gray-50 px-4 py-3 shadow-[-8px_0_8px_-8px_rgba(0,0,0,0.15)]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-4 align-top font-mono text-sm font-semibold text-gray-900">{{ $user->id }}</td>
                                <td class="px-4 py-4 align-top">
                                    <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                                    <p class="text-gray-500">{{ $user->email }}</p>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    @if ($user->participantProfile)
                                        <form method="POST" action="{{ route('admin.users.therapy-start', $user) }}" class="space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <label class="block text-xs text-gray-500" for="therapy-start-{{ $user->id }}">Start date</label>
                                            <input
                                                id="therapy-start-{{ $user->id }}"
                                                type="date"
                                                name="therapy_started_on"
                                                value="{{ $user->participantProfile->therapy_started_on?->format('Y-m-d') }}"
                                                class="rounded-md border-gray-300 text-sm"
                                            >
                                            <label class="block text-xs text-gray-500" for="case-manager-{{ $user->id }}">Case manager email</label>
                                            <input
                                                id="case-manager-{{ $user->id }}"
                                                type="email"
                                                name="case_manager_email"
                                                value="{{ $user->participantProfile->case_manager_email }}"
                                                placeholder="alerts@example.com"
                                                class="block w-44 rounded-md border-gray-300 text-sm"
                                            >
                                            <button type="submit" class="block rounded-md bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-500">Save</button>
                                        </form>
                                        @if ($user->participantProfile->therapy_started_on)
                                            <ul class="mt-2 space-y-0.5 text-xs text-gray-600">
                                                @foreach (\App\Services\TherapyFollowUpAlerts::MILESTONES as $key => $milestone)
                                                    @php
                                                        $due = $user->participantProfile->therapy_started_on->copy()->addMonths($milestone['months']);
                                                        $sent = isset(($user->participantProfile->therapy_alerts_sent ?? [])[$key]);
                                                    @endphp
                                                    <li>{{ $milestone['label'] }} alert {{ $due->format('M j, Y') }}@if ($sent) — sent @endif</li>
                                                @endforeach
                                            </ul>
                                            @unless ($user->participantProfile->case_manager_email)
                                                <p class="mt-1 text-xs text-amber-800">Add a case manager email or these alerts will not be sent.</p>
                                            @endunless
                                        @endif
                                    @else
                                        <p class="text-xs text-gray-500">Participants only</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <form
                                        method="POST"
                                        action="{{ route('admin.users.roles', $user) }}"
                                        class="space-y-2"
                                        x-data="{
                                            learner: {{ $user->hasRole(\App\Enums\UserRole::Learner) ? 'true' : 'false' }},
                                            participant: {{ $user->hasRole(\App\Enums\UserRole::Participant) ? 'true' : 'false' }},
                                            pickLearner() { this.learner = true; this.participant = false; },
                                            pickParticipant() { this.participant = true; this.learner = false; },
                                        }"
                                    >
                                        @csrf
                                        @foreach ($roles as $role)
                                            <label class="flex items-center gap-2">
                                                @if ($role === \App\Enums\UserRole::Learner)
                                                    <input type="checkbox" name="roles[]" value="{{ $role->value }}" x-model="learner" @change="if (learner) pickLearner()">
                                                @elseif ($role === \App\Enums\UserRole::Participant)
                                                    <input type="checkbox" name="roles[]" value="{{ $role->value }}" x-model="participant" @change="if (participant) pickParticipant()">
                                                @else
                                                    <input type="checkbox" name="roles[]" value="{{ $role->value }}" @checked($user->hasRole($role))>
                                                @endif
                                                {{ $role->label() }}
                                            </label>
                                        @endforeach
                                        <p class="text-xs text-gray-500">Learner and Participant are mutually exclusive.</p>
                                        <button type="submit" class="rounded-md border border-gray-300 px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">Save roles</button>
                                    </form>
                                </td>
                                <td class="px-4 py-4 align-top space-y-3">
                                    <form method="POST" action="{{ route('admin.users.courses', $user) }}" class="flex flex-wrap gap-2">
                                        @csrf
                                        <select name="course_id" class="rounded-md border-gray-300 text-sm" style="width: 14rem; max-width: 100%;" required>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-semibold text-white">Grant access</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.posttest-reset', $user) }}" class="flex flex-wrap gap-2" onsubmit="return confirm('Reset this posttest so the learner can retake it once?')">
                                        @csrf
                                        <select name="course_id" class="rounded-md border-gray-300 text-sm" style="width: 14rem; max-width: 100%;" required>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-1 text-xs font-semibold text-red-700">Reset posttest</button>
                                    </form>
                                </td>
                                <td class="sticky right-0 z-10 bg-white px-4 py-4 align-top space-y-3 shadow-[-8px_0_8px_-8px_rgba(0,0,0,0.15)]">
                                    <details>
                                        <summary class="cursor-pointer text-xs font-semibold text-indigo-700 hover:text-indigo-600">Reset password</summary>
                                        <form method="POST" action="{{ route('admin.users.password', $user) }}" class="mt-2 space-y-2" onsubmit="return confirm('Set a new password for {{ $user->name }}?')">
                                            @csrf
                                            @method('PUT')
                                            <input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="New password (8+ characters)" class="block w-48 rounded-md border-gray-300 text-xs">
                                            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="Confirm new password" class="block w-48 rounded-md border-gray-300 text-xs">
                                            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-500">Save new password</button>
                                        </form>
                                    </details>
                                    @if ($user->id === auth()->id())
                                        <p class="text-xs text-gray-500">Current account</p>
                                    @else
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.destroy', $user) }}"
                                            onsubmit="return confirm('Delete {{ $user->name }} ({{ $user->email }})? This cannot be undone.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md border border-red-300 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 hover:bg-red-100">
                                                Delete account
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
