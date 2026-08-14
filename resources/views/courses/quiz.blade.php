<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $quiz->title }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-800">
                        Answer every question before submitting.
                    </div>
                @endif

                <form method="POST" action="{{ route('courses.quiz.store', [$course, $quiz->kind->value]) }}" class="space-y-8">
                    @csrf
                    @foreach ($quiz->items as $item)
                        <fieldset>
                            <legend class="font-semibold text-gray-900">{{ $item['prompt'] }}</legend>
                            <div class="mt-3 space-y-2">
                                @foreach ($item['choices'] as $key => $label)
                                    <label class="flex items-start gap-2 text-sm text-gray-700">
                                        <input type="radio" name="{{ $item['id'] }}" value="{{ $key }}" class="mt-1" required>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    <div class="flex items-center justify-between">
                        <a href="{{ route('courses.show', $course) }}" class="text-sm font-semibold text-gray-600 hover:text-gray-800">Cancel</a>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
