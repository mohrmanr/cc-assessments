<?php

namespace App\Support;

class SurveyScaleConfig
{
    /**
     * @param  array<string, mixed>  $scoringConfig
     * @param  array<string, mixed>|null  $surveyConfig
     * @return array<string, mixed>
     */
    public static function resolve(array $scoringConfig, ?array $surveyConfig = null): array
    {
        $surveyConfig ??= [];
        $scaleType = $scoringConfig['scale_type'] ?? ($surveyConfig['scale_type'] ?? 'discrete');
        $scaleMode = $scoringConfig['scale_mode'] ?? 'manual';

        if ($scaleType === 'continuous') {
            $answerType = 'slider';
        } elseif ($scaleMode === 'buckets') {
            $answerType = 'buckets';
        } else {
            $answerType = 'custom';
        }

        $scaleLabels = array_merge(
            ['left' => '', 'center' => '', 'right' => ''],
            $scoringConfig['scale_labels'] ?? ($surveyConfig['scale_labels'] ?? [])
        );

        return [
            'answer_type' => $answerType,
            'scale_type' => $scaleType,
            'scale_mode' => $scaleMode,
            'bucket_min' => (int) ($scoringConfig['bucket_min'] ?? 1),
            'bucket_max' => (int) ($scoringConfig['bucket_max'] ?? 5),
            'bucket_count' => (int) ($scoringConfig['bucket_count'] ?? 5),
            'bucket_label_suffix' => (string) ($scoringConfig['bucket_label_suffix'] ?? ''),
            'min' => (int) ($scoringConfig['min'] ?? ($surveyConfig['min'] ?? 0)),
            'max' => (int) ($scoringConfig['max'] ?? ($surveyConfig['max'] ?? 100)),
            'step' => (int) ($scoringConfig['step'] ?? ($surveyConfig['step'] ?? 1)),
            'scale_labels' => $scaleLabels,
        ];
    }

    /**
     * @return array<int|string, string>
     */
    public static function generateBucketLabels(int $min, int $max, int $count, string $suffix = ''): array
    {
        if ($count < 2) {
            throw new \InvalidArgumentException('Bucket count must be at least 2.');
        }

        if ($min > $max) {
            throw new \InvalidArgumentException('Minimum value must be less than or equal to maximum value.');
        }

        $labels = [];

        for ($index = 0; $index < $count; $index++) {
            $value = (int) round($min + (($max - $min) * $index) / ($count - 1));
            $labels[(string) $value] = $suffix !== '' ? "{$value}{$suffix}" : (string) $value;
        }

        return $labels;
    }

    public static function usesContinuousScale(array $survey, array $scoringConfig = []): bool
    {
        return ($survey['scale_type'] ?? null) === 'continuous'
            || ($scoringConfig['scale_type'] ?? null) === 'continuous';
    }
}
