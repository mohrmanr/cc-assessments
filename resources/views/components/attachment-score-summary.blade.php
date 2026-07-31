@props([
    'result',
])

@php
    $targets = \App\Support\AttachmentQuadrantPresenter::targetDimensions($result->subscale_scores['dimensions'] ?? []);
@endphp

@if ($targets !== [])
    <div {{ $attributes->merge(['class' => 'mt-3 rounded-md border border-indigo-100 bg-indigo-50/60 p-3']) }}>
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-800">Attachment scores (anxiety / avoidance)</p>
        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            @foreach (\App\Support\AttachmentSurvey::TARGETS as $targetKey => $targetLabel)
                @php
                    $pair = $targets[$targetKey] ?? null;
                @endphp
                @if ($pair)
                    <div class="rounded border border-indigo-100 bg-white px-3 py-2 text-sm text-gray-800">
                        <p class="font-semibold text-gray-900">{{ $targetLabel }}</p>
                        <p class="mt-1 text-gray-700">
                            Anxiety: <strong>{{ number_format((float) ($pair['anxiety'] ?? 0), 2) }}</strong>
                            <span class="mx-1 text-gray-300">|</span>
                            Avoidance: <strong>{{ number_format((float) ($pair['avoidance'] ?? 0), 2) }}</strong>
                        </p>
                    </div>
                @endif
            @endforeach
        </div>
        @php
            $overallAnxiety = $result->subscale_scores['dimensions']['attachment_anxiety'] ?? null;
            $overallAvoidance = $result->subscale_scores['dimensions']['attachment_avoidance'] ?? null;
        @endphp
        @if ($overallAnxiety !== null || $overallAvoidance !== null)
            <p class="mt-2 text-xs text-indigo-900">
                Overall average —
                @if ($overallAnxiety !== null)
                    Anxiety: <strong>{{ number_format((float) $overallAnxiety, 2) }}</strong>
                @endif
                @if ($overallAnxiety !== null && $overallAvoidance !== null)
                    ·
                @endif
                @if ($overallAvoidance !== null)
                    Avoidance: <strong>{{ number_format((float) $overallAvoidance, 2) }}</strong>
                @endif
            </p>
        @endif
    </div>
@endif
