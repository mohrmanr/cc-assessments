<?php

namespace App\Services;

use App\Models\Instrument;

class InstrumentScorer
{
    /**
     * @param  array<string, int>  $itemResponses
     * @return array{total: float, threshold_met: bool, threshold: float|int|null}
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
     * @return array{total: float, threshold_met: bool, threshold: float|int|null}
     */
    public function score(Instrument $instrument, array $itemResponses): array
    {
        if ($instrument->slug === 'pcl-5') {
            return $this->scorePcl5Demo($itemResponses);
        }

        $config = $instrument->scoring_config ?? [];
        $method = $config['method'] ?? 'sum';
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
}
