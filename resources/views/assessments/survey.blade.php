<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $survey['title'] }}</h2>
    </x-slot>

    <div class="py-12" style="padding-bottom: 5rem;">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-6">
                @if (! empty($survey['intro']))
                    <div class="space-y-3 text-sm text-gray-700">
                        @foreach ($survey['intro'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                @endif

                <p class="text-sm text-gray-600">
                    {{ $survey['instructions'] }} Select one answer per question, then submit.
                </p>

                <form method="POST" action="{{ route($survey['store_route_name']) }}">
                    @csrf

                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 0.5rem;">
                        <button
                            type="submit"
                            style="display: block; width: 100%; padding: 0.875rem 1.5rem; background: #4f46e5; color: #fff; font-size: 1rem; font-weight: 600; border: none; border-radius: 0.375rem; cursor: pointer;"
                        >
                            Submit
                        </button>
                        <p style="margin-top: 0.5rem; font-size: 0.75rem; color: #4338ca; text-align: center;">
                            You can submit from here or use the same button after the last question.
                        </p>
                    </div>

                    @foreach (($survey['fields'] ?? []) as $index => $field)
                        <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem; margin-bottom: 1.5rem;">
                            <label for="{{ $field['id'] }}" style="display: block; font-size: 0.875rem; font-weight: 500; color: #111827; margin-bottom: 0.5rem;">
                                {{ $index + 1 }}. {{ $field['label'] }}
                            </label>
                            <input
                                id="{{ $field['id'] }}"
                                type="{{ $field['type'] ?? 'text' }}"
                                name="{{ $field['id'] }}"
                                value="{{ old($field['id'], ($field['type'] ?? 'text') === 'email' ? auth()->user()->email : '') }}"
                                maxlength="{{ $field['max'] ?? '' }}"
                                autocomplete="{{ $field['autocomplete'] ?? 'off' }}"
                                required
                                style="display: block; width: 100%; border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem 0.75rem; font-size: 0.875rem;"
                            >
                            @error($field['id'])
                                <p style="margin-top: 0.375rem; font-size: 0.75rem; color: #b91c1c;">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    @foreach ($items as $index => $item)
                        <fieldset style="border: none; border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem; margin-bottom: 1.5rem;">
                            <legend style="font-size: 0.875rem; font-weight: 500; color: #111827; margin-bottom: 0.5rem;">
                                {{ $index + 1 + count($survey['fields'] ?? []) }}. {{ $item['text'] }}
                            </legend>
                            <div>
                                @foreach ($labels as $value => $label)
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #374151; margin-bottom: 0.375rem; cursor: pointer;">
                                        <input
                                            type="radio"
                                            name="{{ $item['id'] }}"
                                            value="{{ $value }}"
                                            @checked(old($item['id'], $survey['default']) == $value)
                                            required
                                        >
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #e5e7eb;">
                        <button
                            type="submit"
                            style="display: block; width: 100%; padding: 1rem 1.5rem; background: #4f46e5; color: #fff; font-size: 1.0625rem; font-weight: 600; border: none; border-radius: 0.375rem; cursor: pointer;"
                        >
                            Submit
                        </button>
                    </div>
                </form>

                @if (! empty($survey['closing']))
                    <p class="text-sm text-gray-600">{{ $survey['closing'] }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
