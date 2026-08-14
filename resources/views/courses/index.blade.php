<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Courses</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @forelse ($courses as $course)
                @php $steps = $states[$course->id]; @endphp
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-lg text-gray-900">{{ $course->title }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ $course->description }}</p>
                            @if ($course->requires_payment)
                                <p class="mt-2 text-sm text-gray-500">
                                    ${{ number_format($course->price_cents / 100, 2) }}
                                    @if ($steps['pay_done'])
                                        <span class="ml-2 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Access granted</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                        <a href="{{ route('courses.show', $course) }}" class="inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Open course
                        </a>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm rounded-lg p-6 text-sm text-gray-600">
                    No courses are available yet.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
