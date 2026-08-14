<?php

namespace App\Support;

use App\Models\AssessmentResult;
use Illuminate\Support\Collection;

class AttachmentQuadrantPresenter
{
    /** @var array<string, string> */
    public const TARGETS = [
        'mother' => 'Mother',
        'father' => 'Father',
        'partner' => 'Partner',
        'best_friend' => 'Best Friend',
    ];

    /**
     * @param  Collection<int, AssessmentResult>  $ecrResults
     * @return array<int, array{key: string, label: string, points: Collection<int, array<string, mixed>>}>
     */
    public static function chartsForSeries(Collection $ecrResults): array
    {
        $ordered = $ecrResults->sortBy('administered_at')->values();
        if ($ordered->isEmpty()) {
            return [];
        }

        $discovered = collect();
        foreach ($ordered as $result) {
            $discovered = $discovered->merge(array_keys(self::targetDimensions($result->subscale_scores['dimensions'] ?? [])));
        }
        $discovered = $discovered->unique()->values();

        $targetKeys = collect(array_keys(self::TARGETS))
            ->filter(fn (string $key): bool => $discovered->contains($key))
            ->values();

        if ($discovered->contains('overall')) {
            $targetKeys = $targetKeys->push('overall');
        }

        if ($targetKeys->isEmpty()) {
            $targetKeys = $discovered;
        }

        return $targetKeys
            ->map(function (string $targetKey) use ($ordered): ?array {
                $points = $ordered
                    ->map(function (AssessmentResult $result, int $index) use ($targetKey): ?array {
                        $dimensions = $result->subscale_scores['dimensions'] ?? [];
                        $targets = self::targetDimensions($dimensions);
                        if (! isset($targets[$targetKey])) {
                            return null;
                        }

                        $anxiety = (float) $targets[$targetKey]['anxiety'];
                        $avoidance = (float) $targets[$targetKey]['avoidance'];
                        $plot = self::plotPoint($anxiety, $avoidance);

                        return [
                            'anxiety' => $anxiety,
                            'avoidance' => $avoidance,
                            'date' => $result->administered_at->format('M Y'),
                            'window' => ['Baseline', '4 months', '8 months', '12 months'][$index] ?? 'Follow-up',
                            'quadrant' => self::quadrantLabel($anxiety, $avoidance),
                            'x' => $plot['x'],
                            'y' => $plot['y'],
                        ];
                    })
                    ->filter()
                    ->values();

                if ($points->isEmpty()) {
                    return null;
                }

                return [
                    'key' => $targetKey,
                    'label' => self::TARGETS[$targetKey] ?? str($targetKey)->replace('_', ' ')->title()->toString(),
                    'points' => $points,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $dimensions
     * @return array<string, array{anxiety: float, avoidance: float}>
     */
    public static function targetDimensions(array $dimensions): array
    {
        if (isset($dimensions['targets']) && is_array($dimensions['targets'])) {
            return collect($dimensions['targets'])
                ->map(function (array $pair): array {
                    return [
                        'anxiety' => (float) ($pair['anxiety'] ?? $pair['attachment_anxiety'] ?? 0),
                        'avoidance' => (float) ($pair['avoidance'] ?? $pair['attachment_avoidance'] ?? 0),
                    ];
                })
                ->all();
        }

        $targets = [];
        foreach (self::TARGETS as $key => $label) {
            $anxiety = $dimensions["{$key}_anxiety"] ?? $dimensions["{$key}_attachment_anxiety"] ?? null;
            $avoidance = $dimensions["{$key}_avoidance"] ?? $dimensions["{$key}_attachment_avoidance"] ?? null;

            if ($anxiety !== null && $avoidance !== null) {
                $targets[$key] = [
                    'anxiety' => (float) $anxiety,
                    'avoidance' => (float) $avoidance,
                ];
            }
        }

        if ($targets !== []) {
            return $targets;
        }

        if (isset($dimensions['attachment_anxiety'], $dimensions['attachment_avoidance'])) {
            return [
                'overall' => [
                    'anxiety' => (float) $dimensions['attachment_anxiety'],
                    'avoidance' => (float) $dimensions['attachment_avoidance'],
                ],
            ];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $dimensions
     * @return array<string, mixed>
     */
    public static function flattenDimensions(array $dimensions): array
    {
        if (! isset($dimensions['targets']) || ! is_array($dimensions['targets'])) {
            return collect($dimensions)->except('targets')->all();
        }

        $flat = collect($dimensions)->except('targets')->all();
        foreach ($dimensions['targets'] as $targetKey => $pair) {
            if (! is_array($pair)) {
                continue;
            }
            $flat["{$targetKey}_anxiety"] = $pair['anxiety'] ?? null;
            $flat["{$targetKey}_avoidance"] = $pair['avoidance'] ?? null;
        }

        return $flat;
    }

    /**
     * Compact score label for tables: anxiety / avoidance when present, else total.
     *
     * @return array{primary: string, secondary: string|null, is_attachment: bool}
     */
    public static function compactScore(AssessmentResult $result): array
    {
        $dimensions = $result->subscale_scores['dimensions'] ?? [];
        $anxiety = $dimensions['attachment_anxiety'] ?? null;
        $avoidance = $dimensions['attachment_avoidance'] ?? null;

        if ($anxiety === null || $avoidance === null) {
            $targets = self::targetDimensions(is_array($dimensions) ? $dimensions : []);
            if ($targets !== []) {
                $anxVals = collect($targets)->pluck('anxiety')->filter(fn ($v) => $v !== null);
                $avdVals = collect($targets)->pluck('avoidance')->filter(fn ($v) => $v !== null);
                if ($anxVals->isNotEmpty() && $avdVals->isNotEmpty()) {
                    $anxiety = round((float) $anxVals->avg(), 1);
                    $avoidance = round((float) $avdVals->avg(), 1);
                }
            }
        }

        if ($anxiety !== null && $avoidance !== null) {
            return [
                'primary' => number_format((float) $anxiety, 1).' / '.number_format((float) $avoidance, 1),
                'secondary' => 'Anxiety / Avoidance',
                'is_attachment' => true,
            ];
        }

        if ($result->total_score === null) {
            return [
                'primary' => '—',
                'secondary' => null,
                'is_attachment' => false,
            ];
        }

        return [
            'primary' => number_format((float) $result->total_score, 1),
            'secondary' => null,
            'is_attachment' => false,
        ];
    }

    public static function quadrantLabel(float $anxiety, float $avoidance): string
    {
        return match (true) {
            $anxiety < 4 && $avoidance < 4 => 'Secure',
            $anxiety >= 4 && $avoidance < 4 => 'Preoccupied',
            $anxiety < 4 && $avoidance >= 4 => 'Dismissing',
            default => 'Fearful',
        };
    }

    /**
     * @return array{x: float, y: float}
     */
    public static function plotPoint(float $anxiety, float $avoidance): array
    {
        return [
            'x' => 70 + (($anxiety - 1) / 6) * 420,
            'y' => 490 - (($avoidance - 1) / 6) * 420,
        ];
    }
}
