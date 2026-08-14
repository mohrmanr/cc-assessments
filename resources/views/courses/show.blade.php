<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $course->title }}</h2>
            <a href="{{ route('courses.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">All courses</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <p class="text-sm text-gray-600">{{ $course->description }}</p>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="bg-white shadow-sm rounded-lg p-5 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">1. Pay</p>
                    <p class="text-sm text-gray-700">Record access for this course. Payment processing is a placeholder.</p>
                    @if ($steps['pay_done'])
                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Complete</span>
                    @elseif ($steps['pay_enabled'])
                        <form method="POST" action="{{ route('courses.pay', $course) }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                Pay ${{ number_format($course->price_cents / 100, 2) }}
                            </button>
                        </form>
                    @endif
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">2. Pretest</p>
                    <p class="text-sm text-gray-700">Complete only. There is no passing score on the pretest.</p>
                    @if ($steps['pretest_done'] && $steps['pretest_present'])
                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Complete</span>
                    @elseif ($steps['pretest_enabled'])
                        <a href="{{ route('courses.quiz', [$course, 'pretest']) }}" class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Start pretest</a>
                    @else
                        <p class="text-xs text-gray-500">Unlocks after Pay.</p>
                    @endif
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">3. Course video</p>
                    <p class="text-sm text-gray-700">Watch the webinar placeholder, then mark it complete.</p>
                    @if ($steps['video_done'])
                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Complete</span>
                        <a href="{{ route('courses.video', $course) }}" class="block text-sm font-semibold text-indigo-600">Replay</a>
                    @elseif ($steps['video_enabled'])
                        <a href="{{ route('courses.video', $course) }}" class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Open video</a>
                    @else
                        <p class="text-xs text-gray-500">Unlocks after Pretest.</p>
                    @endif
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">4. Posttest</p>
                    <p class="text-sm text-gray-700">One attempt. Pass is {{ $course->pass_percent }}%.</p>
                    @if ($steps['posttest_done'])
                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Submitted</span>
                    @elseif ($steps['posttest_enabled'])
                        <a href="{{ route('courses.quiz', [$course, 'posttest']) }}" class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Start posttest</a>
                    @else
                        <p class="text-xs text-gray-500">Unlocks after the video.</p>
                    @endif
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">5. Score</p>
                    @if ($posttest)
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($posttest->score, 1) }}%</p>
                        <p class="text-sm {{ $posttest->passed ? 'text-green-700' : 'text-red-700' }}">
                            {{ $posttest->passed ? 'Passed' : 'Did not pass' }}
                        </p>
                    @else
                        <p class="text-xs text-gray-500">Shown after the posttest.</p>
                    @endif
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">6. Certificate</p>
                    @if ($steps['certificate_enabled'])
                        <a href="{{ route('courses.certificate', $course) }}" class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">View certificate</a>
                    @else
                        <p class="text-xs text-gray-500">Available after a passing posttest.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
