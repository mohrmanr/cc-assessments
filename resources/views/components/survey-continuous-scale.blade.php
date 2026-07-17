@props([
    'name',
    'min' => 0,
    'max' => 100,
    'step' => 1,
    'default' => null,
    'labels' => [],
])

@php
    $leftLabel = $labels['left'] ?? "{$min}";
    $centerLabel = $labels['center'] ?? '';
    $rightLabel = $labels['right'] ?? "{$max}";
    $initialValue = old($name, $default);
@endphp

<div
    class="survey-continuous-scale"
    data-survey-continuous-scale
    data-min="{{ $min }}"
    data-max="{{ $max }}"
    data-step="{{ $step }}"
    data-initial="{{ $initialValue === null || $initialValue === '' ? '' : (int) $initialValue }}"
>
    <div class="survey-continuous-scale__labels">
        <span>{{ $leftLabel }}</span>
        @if ($centerLabel !== '')
            <span class="survey-continuous-scale__label-center">{{ $centerLabel }}</span>
        @endif
        <span class="survey-continuous-scale__label-right">{{ $rightLabel }}</span>
    </div>

    <div class="survey-continuous-scale__controls">
        <div class="survey-continuous-scale__range-wrap">
            <input
                type="range"
                class="survey-continuous-scale__range"
                data-range
                min="{{ $min }}"
                max="{{ $max }}"
                step="{{ $step }}"
                aria-label="Answer for {{ $name }}"
            >
        </div>
        <input
            type="number"
            id="{{ $name }}"
            name="{{ $name }}"
            class="survey-continuous-scale__number"
            data-number
            min="{{ $min }}"
            max="{{ $max }}"
            step="{{ $step }}"
            inputmode="numeric"
            required
            @if ($initialValue !== null && $initialValue !== '')
                value="{{ (int) $initialValue }}"
            @endif
        >
        <button type="button" class="survey-continuous-scale__clear" data-clear>Clear</button>
    </div>

    @error($name)
        <p class="survey-continuous-scale__error">{{ $message }}</p>
    @enderror
</div>
