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
    $hasInitial = $initialValue !== null && $initialValue !== '';
    $rangeValue = $hasInitial ? (int) $initialValue : (int) $min;
@endphp

<div
    class="survey-continuous-scale"
    data-survey-continuous-scale
    data-min="{{ $min }}"
    data-max="{{ $max }}"
    data-step="{{ $step }}"
    data-initial="{{ $hasInitial ? (int) $initialValue : '' }}"
    style="width: 100%; margin-top: 0.5rem;"
>
    <div
        class="survey-continuous-scale__labels"
        style="display: flex; width: 100%; justify-content: space-between; gap: 0.75rem; font-size: 0.75rem; color: #4b5563; margin-bottom: 0.35rem;"
    >
        <button
            type="button"
            data-set-min
            style="border: 0; background: transparent; padding: 0; text-align: left; color: inherit; font: inherit; cursor: pointer; text-decoration: underline; text-underline-offset: 2px;"
        >{{ $leftLabel }}</button>
        @if ($centerLabel !== '')
            <span style="text-align: center; flex: 1;">{{ $centerLabel }}</span>
        @endif
        <button
            type="button"
            data-set-max
            style="border: 0; background: transparent; padding: 0; text-align: right; color: inherit; font: inherit; cursor: pointer; text-decoration: underline; text-underline-offset: 2px;"
        >{{ $rightLabel }}</button>
    </div>

    <div class="survey-continuous-scale__range-wrap" style="width: 100%; margin-bottom: 0.5rem;">
        <input
            type="range"
            class="survey-continuous-scale__range"
            data-range
            min="{{ $min }}"
            max="{{ $max }}"
            step="{{ $step }}"
            value="{{ $rangeValue }}"
            aria-label="Answer for {{ $name }}"
            style="display: block; width: 100%; height: 1.25rem; margin: 0; cursor: pointer; accent-color: #4f46e5;"
        >
    </div>

    <div class="survey-continuous-scale__controls" style="display: flex; align-items: center; gap: 0.75rem;">
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
            value="{{ $rangeValue }}"
            style="width: 4.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.25rem 0.5rem; text-align: center; font-size: 0.875rem;"
        >
        <button
            type="button"
            class="survey-continuous-scale__clear"
            data-clear
            style="border: 0; background: transparent; color: #4f46e5; font-size: 0.875rem; font-weight: 600; cursor: pointer; padding: 0;"
        >
            Clear
        </button>
    </div>

    @error($name)
        <p class="survey-continuous-scale__error" style="margin-top: 0.25rem; font-size: 0.75rem; color: #dc2626;">{{ $message }}</p>
    @enderror
</div>
