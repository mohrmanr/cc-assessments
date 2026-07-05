<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $participant->user->name }} Results</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-900">Assessment Score History</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $participant->user->email }} - baseline through one-year development data.
                        </p>
                    </div>
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Back to admin
                    </a>
                </div>
            </div>

            @if (! empty($attachmentQuadrantCharts))
                <div id="attachment-quadrants" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-lg text-gray-900">ECR-RS Attachment Quadrants</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Each chart tracks anxiety and avoidance over time for one attachment figure (mother, father, partner, best friend).
                                Quadrant labels are visual shorthand, not a diagnosis.
                            </p>
                        </div>
                        <a href="#attachment-quadrants" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                            Link to graphics
                        </a>
                    </div>
                    <div class="mt-6 grid gap-6 lg:grid-cols-2">
                        @foreach ($attachmentQuadrantCharts as $chart)
                            <x-attachment-quadrant-chart
                                :title="$chart['label']"
                                :points="$chart['points']"
                                :anchor-id="'attachment-'.$chart['key']"
                            />
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($results as $label => $series)
                @php
                    $ordered = $series->sortBy('administered_at')->values();
                    $dimensionKeys = $ordered
                        ->flatMap(fn ($result) => array_keys(\App\Support\AttachmentQuadrantPresenter::flattenDimensions($result->subscale_scores['dimensions'] ?? [])))
                        ->unique()
                        ->values();
                    $chartKeys = $dimensionKeys->isNotEmpty() ? $dimensionKeys : collect(['total']);
                    $colors = ['#4f46e5', '#059669', '#dc2626', '#d97706', '#7c3aed'];
                    $maxScore = max(1, $ordered->flatMap(function ($result) use ($chartKeys) {
                        $dimensions = \App\Support\AttachmentQuadrantPresenter::flattenDimensions($result->subscale_scores['dimensions'] ?? []);
                        return $chartKeys->map(fn ($key) => $key === 'total'
                            ? (float) $result->total_score
                            : (float) ($dimensions[$key] ?? 0));
                    })->max());
                    $chartSeries = $chartKeys->map(function ($key, $seriesIndex) use ($ordered, $maxScore, $colors) {
                        $points = $ordered->map(function ($result, $index) use ($ordered, $maxScore, $key) {
                            $count = max(1, $ordered->count() - 1);
                            $dimensions = \App\Support\AttachmentQuadrantPresenter::flattenDimensions($result->subscale_scores['dimensions'] ?? []);
                            $score = $key === 'total'
                                ? (float) $result->total_score
                                : (float) ($dimensions[$key] ?? 0);
                            $x = 60 + ($index / $count) * 620;
                            $y = 220 - ($score / $maxScore) * 170;

                            return [
                                'x' => $x,
                                'y' => $y,
                                'score' => $score,
                                'date' => $result->administered_at->format('M Y'),
                            ];
                        });

                        return [
                            'label' => $key === 'total' ? 'Total' : str($key)->replace('_', ' ')->title()->toString(),
                            'color' => $colors[$seriesIndex % count($colors)],
                            'points' => $points,
                            'polyline' => $points->map(fn ($point) => $point['x'].','.$point['y'])->implode(' '),
                        ];
                    });
                @endphp
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-lg text-gray-900">{{ $label }}</h3>
                    <div class="mt-3 flex flex-wrap gap-3 text-xs text-gray-600">
                        @foreach ($chartSeries as $line)
                            <span class="inline-flex items-center gap-1">
                                <span class="inline-block h-2 w-5 rounded" style="background-color: {{ $line['color'] }}"></span>
                                {{ $line['label'] }}
                            </span>
                        @endforeach
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <svg width="740" height="290" role="img" aria-label="{{ $label }} score history">
                            <line x1="60" y1="220" x2="700" y2="220" stroke="#d1d5db" />
                            <line x1="60" y1="40" x2="60" y2="220" stroke="#d1d5db" />
                            <text x="20" y="45" font-size="12" fill="#6b7280">{{ $maxScore }}</text>
                            <text x="35" y="224" font-size="12" fill="#6b7280">0</text>
                            @foreach ($chartSeries as $line)
                                <polyline points="{{ $line['polyline'] }}" fill="none" stroke="{{ $line['color'] }}" stroke-width="3" />
                                @foreach ($line['points'] as $point)
                                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5" fill="{{ $line['color'] }}" />
                                    <text x="{{ $point['x'] - 8 }}" y="{{ $point['y'] - 10 }}" font-size="12" fill="#111827">{{ rtrim(rtrim(number_format($point['score'], 2), '0'), '.') }}</text>
                                @endforeach
                            @endforeach
                            @foreach ($chartSeries->first()['points'] as $point)
                                <text x="{{ $point['x'] - 24 }}" y="245" font-size="12" fill="#6b7280">{{ $point['date'] }}</text>
                            @endforeach
                        </svg>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pr-4">Date</th>
                                    <th class="py-2 pr-4">Window</th>
                                    <th class="py-2 pr-4">Total Score</th>
                                    <th class="py-2 pr-4">Dimensions</th>
                                    <th class="py-2 pr-4">Threshold</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($ordered as $index => $result)
                                    <tr>
                                        <td class="py-2 pr-4 text-gray-900">{{ $result->administered_at->format('M j, Y') }}</td>
                                        <td class="py-2 pr-4 text-gray-600">{{ ['Baseline', '4 months', '8 months', '12 months'][$index] ?? 'Follow-up' }}</td>
                                        <td class="py-2 pr-4 text-gray-900">{{ $result->total_score }}</td>
                                        <td class="py-2 pr-4 text-gray-600">
                                            @php
                                                $flatDimensions = \App\Support\AttachmentQuadrantPresenter::flattenDimensions($result->subscale_scores['dimensions'] ?? []);
                                            @endphp
                                            @forelse ($flatDimensions as $dimension => $score)
                                                <div>{{ str($dimension)->replace('_', ' ')->title() }}: {{ $score }}</div>
                                            @empty
                                                <span>Total score only</span>
                                            @endforelse
                                        </td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $result->threshold_met ? 'Met' : 'Not met' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
