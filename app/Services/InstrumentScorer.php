<?php

namespace App\Services;

use App\Models\Instrument;
use App\Support\AttachmentSurvey;

class InstrumentScorer
{
    /**
     * @param  array<string, int>  $itemResponses
     * @return array{total: float, threshold_met: bool, threshold: float|int|null, subscale_scores?: array<string, mixed>}
     */
    public function scorePcl5Demo(array $itemResponses): array
    {
        $total = array_sum($itemResponses);
        $threshold = config('portal.pcl5_demo_threshold', 13);

        return [
            'total' => $total,
            'threshold_met' => $total >= $threshold,
            'threshold' => $threshold,
        ];
    }

    /**
     * @param  array<string, int>  $itemResponses
     * @return array{total: float, threshold_met: bool, threshold: float|int|null, subscale_scores?: array<string, mixed>}
     */
    public function score(Instrument $instrument, array $itemResponses): array
    {
        if ($instrument->slug === 'pcl-5') {
            return $this->scorePcl5Demo($itemResponses);
        }

        $config = $instrument->scoring_config ?? [];
        $method = $config['method'] ?? 'sum';

        if ($method === 'attachment_targets') {
            return $this->scoreAttachmentTargets($instrument, $itemResponses);
        }

        $total = match ($method) {
            'mean_x100' => count($itemResponses) > 0 ? array_sum($itemResponses) / count($itemResponses) : 0,
            default => array_sum($itemResponses),
        };
        $threshold = $config['threshold'] ?? null;
        $direction = $config['direction'] ?? 'above';

        $thresholdMet = $threshold !== null && (
            $direction === 'below' ? $total <= $threshold : $total >= $threshold
        );

        return [
            'total' => $total,
            'threshold_met' => $thresholdMet,
            'threshold' => $threshold,
        ];
    }

    /**
     * @param  array<string, int>  $itemResponses
     * @return array{total: float, threshold_met: bool, threshold: float|int|null, subscale_scores: array<string, mixed>}
     */
    protected function scoreAttachmentTargets(Instrument $instrument, array $itemResponses): array
    {
        $config = $instrument->scoring_config ?? [];
        $items = AttachmentSurvey::ensureTargetItems($instrument->items ?? [], $config);
        $itemsById = collect($items)->keyBy('id');
        $scaleMax = (int) ($config['scale_max'] ?? 7);
        $targets = [];

        foreach ($itemsById as $itemId => $item) {
            if (! array_key_exists($itemId, $itemResponses)) {
                continue;
            }

            $target = (string) ($item['target'] ?? '');
            $subscale = (string) ($item['subscale'] ?? '');
            if ($target === '' || ! in_array($subscale, ['anxiety', 'avoidance'], true)) {
                continue;
            }

            $raw = (int) $itemResponses[$itemId];
            $value = ! empty($item['reverse_score'])
                ? ($scaleMax + 1) - $raw
                : $raw;

            $targets[$target][$subscale][] = $value;
        }

        $targetMeans = [];
        foreach ($targets as $targetKey => $subscales) {
            $anxietyValues = $subscales['anxiety'] ?? [];
            $avoidanceValues = $subscales['avoidance'] ?? [];

            $targetMeans[$targetKey] = [
                'anxiety' => $anxietyValues !== []
                    ? round(array_sum($anxietyValues) / count($anxietyValues), 2)
                    : null,
                'avoidance' => $avoidanceValues !== []
                    ? round(array_sum($avoidanceValues) / count($avoidanceValues), 2)
                    : null,
            ];
        }

        $anxietyOverall = collect($targetMeans)
            ->pluck('anxiety')
            ->filter(fn ($value) => $value !== null)
            ->avg();
        $avoidanceOverall = collect($targetMeans)
            ->pluck('avoidance')
            ->filter(fn ($value) => $value !== null)
            ->avg();

        $total = collect([$anxietyOverall, $avoidanceOverall])
            ->filter(fn ($value) => $value !== null)
            ->avg() ?? 0.0;
        $total = round((float) $total, 2);

        $threshold = $config['threshold'] ?? null;
        $direction = $config['direction'] ?? 'above';
        $thresholdMet = $threshold !== null && (
            $direction === 'below' ? $total <= $threshold : $total >= $threshold
        );

        return [
            'total' => $total,
            'threshold_met' => $thresholdMet,
            'threshold' => $threshold,
            'subscale_scores' => [
                'dimensions' => [
                    'targets' => $targetMeans,
                    'attachment_anxiety' => $anxietyOverall !== null ? round((float) $anxietyOverall, 2) : null,
                    'attachment_avoidance' => $avoidanceOverall !== null ? round((float) $avoidanceOverall, 2) : null,
                ],
            ],
        ];
    }
}
