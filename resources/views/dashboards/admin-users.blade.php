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

            <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Account</th>
                            <th class="px-4 py-3">Roles</th>
                            <th class="px-4 py-3">Course access</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-4 align-top">
                                    <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                                    <p class="text-gray-500">{{ $user->email }}</p>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <form method="POST" action="{{ route('admin.users.roles', $user) }}" class="space-y-2">
                                        @csrf
                                        @foreach ($roles as $role)
                                            <label class="flex items-center gap-2">
                                                <input type="checkbox" name="roles[]" value="{{ $role->value }}" @checked($user->hasRole($role))>
                                                {{ $role->label() }}
                                            </label>
                                        @endforeach
                                        <button type="submit" class="rounded-md border border-gray-300 px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">Save roles</button>
                                    </form>
                                </td>
                                <td class="px-4 py-4 align-top space-y-3">
                                    <form method="POST" action="{{ route('admin.users.courses', $user) }}" class="flex flex-wrap gap-2">
                                        @csrf
                                        <select name="course_id" class="rounded-md border-gray-300 text-sm" required>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-semibold text-white">Grant access</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.posttest-reset', $user) }}" class="flex flex-wrap gap-2" onsubmit="return confirm('Reset this posttest so the learner can retake it once?')">
                                        @csrf
                                        <select name="course_id" class="rounded-md border-gray-300 text-sm" required>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-1 text-xs font-semibold text-red-700">Reset posttest</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
