<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Course video</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <p class="font-semibold text-gray-900">{{ $course->title }}</p>
                <div class="rounded-lg bg-gray-900 text-white p-10 text-center">
                    <p class="text-lg font-semibold">Video placeholder</p>
                    <p class="mt-2 text-sm text-gray-300">{{ $course->video_placeholder ?: 'Webinar player will go here.' }}</p>
                </div>
                @if ($steps['video_enabled'])
                    <form method="POST" action="{{ route('courses.video.complete', $course) }}">
                        @csrf
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Mark webinar complete
                        </button>
                    </form>
                @else
                    <p class="text-sm text-green-700">This webinar is already marked complete.</p>
                @endif
                <a href="{{ route('courses.show', $course) }}" class="inline-block text-sm font-semibold text-indigo-600">Back to course</a>
            </div>
        </div>
    </div>
</x-app-layout>
