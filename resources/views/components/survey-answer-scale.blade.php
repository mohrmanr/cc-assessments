@props([
    'name',
    'labels' => [],
    'default' => null,
    'wide' => null,
])

@php
    $selected = old($name, $default);
    $labelCount = count($labels);
    $maxLabelLength = $labelCount > 0 ? max(array_map(static fn ($label) => strlen((string) $label), $labels)) : 0;
    $useWideScale = $wide ?? ($labelCount >= 6 || ($labelCount >= 5 && $maxLabelLength > 14));
@endphp

<div @class([
    'survey-answer-scale',
    'survey-answer-scale--wide' => $useWideScale,
])>
    @if ($useWideScale)
        <p class="survey-answer-scale__hint">Swipe sideways to see all answer choices.</p>
    @endif

    <div class="survey-answer-scale__track">
        @foreach ($labels as $value => $label)
            <label class="survey-answer-scale__option">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $value }}"
                    @checked((string) $selected === (string) $value)
                    required
                >
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>
</div>
