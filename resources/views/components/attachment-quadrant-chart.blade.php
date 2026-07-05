@props([
    'title',
    'points',
    'anchorId' => null,
])

@php
    $pointCollection = collect($points);
@endphp

<div @if($anchorId) id="{{ $anchorId }}" @endif class="rounded-lg border border-gray-200 p-4">
    <h4 class="font-semibold text-gray-900">{{ $title }}</h4>
    <p class="mt-1 text-xs text-gray-500">Anxiety and avoidance over time for this attachment figure.</p>
    <div class="mt-3 overflow-x-auto">
        <svg width="100%" viewBox="0 0 560 560" class="max-w-md mx-auto" role="img" aria-label="{{ $title }} attachment quadrant">
            <rect x="70" y="70" width="210" height="210" fill="#fef3c7" />
            <rect x="280" y="70" width="210" height="210" fill="#fee2e2" />
            <rect x="70" y="280" width="210" height="210" fill="#dcfce7" />
            <rect x="280" y="280" width="210" height="210" fill="#dbeafe" />
            <line x1="70" y1="280" x2="490" y2="280" stroke="#374151" stroke-width="2" />
            <line x1="280" y1="70" x2="280" y2="490" stroke="#374151" stroke-width="2" />
            <rect x="70" y="70" width="420" height="420" fill="none" stroke="#374151" stroke-width="2" />

            <text x="140" y="95" font-size="14" fill="#92400e" font-weight="700">Dismissing</text>
            <text x="350" y="95" font-size="14" fill="#991b1b" font-weight="700">Fearful</text>
            <text x="150" y="470" font-size="14" fill="#166534" font-weight="700">Secure</text>
            <text x="340" y="470" font-size="14" fill="#1e40af" font-weight="700">Preoccupied</text>

            <text x="210" y="535" font-size="13" fill="#374151">Anxiety: low to high</text>
            <text x="8" y="290" font-size="13" fill="#374151" transform="rotate(-90 18,290)">Avoidance: low to high</text>
            <text x="64" y="505" font-size="11" fill="#6b7280">1</text>
            <text x="276" y="505" font-size="11" fill="#6b7280">4</text>
            <text x="486" y="505" font-size="11" fill="#6b7280">7</text>
            <text x="50" y="493" font-size="11" fill="#6b7280">1</text>
            <text x="50" y="284" font-size="11" fill="#6b7280">4</text>
            <text x="50" y="74" font-size="11" fill="#6b7280">7</text>

            @if ($pointCollection->count() > 1)
                <polyline points="{{ $pointCollection->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ') }}" fill="none" stroke="#4f46e5" stroke-width="3" />
            @endif
            @foreach ($pointCollection as $index => $point)
                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="7" fill="#4f46e5" />
                <text x="{{ $point['x'] + 10 }}" y="{{ $point['y'] - 8 }}" font-size="12" fill="#111827">{{ $index + 1 }}</text>
            @endforeach
        </svg>
    </div>
    <div class="mt-3 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead>
                <tr class="text-left text-gray-500">
                    <th class="py-1 pr-3">Pt</th>
                    <th class="py-1 pr-3">Window</th>
                    <th class="py-1 pr-3">Anxiety</th>
                    <th class="py-1 pr-3">Avoidance</th>
                    <th class="py-1 pr-3">Quadrant</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($pointCollection as $index => $point)
                    <tr>
                        <td class="py-1 pr-3 text-gray-900">{{ $index + 1 }}</td>
                        <td class="py-1 pr-3 text-gray-600">{{ $point['window'] }}</td>
                        <td class="py-1 pr-3 text-gray-900">{{ $point['anxiety'] }}</td>
                        <td class="py-1 pr-3 text-gray-900">{{ $point['avoidance'] }}</td>
                        <td class="py-1 pr-3 text-gray-600">{{ $point['quadrant'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
