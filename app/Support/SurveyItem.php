<?php

namespace App\Support;

class SurveyItem
{
    public const TYPES = ['scale', 'choice', 'number', 'text'];

    public const NUMBER_MAX = 999;

    public const TEXT_MAX = 1000;

    /**
     * @param  array<string, mixed>  $item
     */
    public static function type(array $item): string
    {
        $type = $item['type'] ?? 'scale';

        return in_array($type, self::TYPES, true) ? $type : 'scale';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function isRequired(array $item): bool
    {
        return ! array_key_exists('required', $item) || filter_var($item['required'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Answer choices for a scale or choice item.
     *
     * @param  array<string, mixed>  $item
     * @param  array<int|string, string>  $sharedLabels
     * @return array<int|string, string>
     */
    public static function choices(array $item, array $sharedLabels): array
    {
        if (self::type($item) === 'choice') {
            return is_array($item['options'] ?? null) ? $item['options'] : [];
        }

        return $sharedLabels;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function usesSharedScale(array $items): bool
    {
        foreach ($items as $item) {
            if (self::type($item) === 'scale') {
                return true;
            }
        }

        return false;
    }

    /**
     * Human-readable answer for admin and clinician views.
     *
     * @param  array<string, mixed>|null  $item
     * @param  array<int|string, string>  $sharedLabels
     */
    public static function displayValue(?array $item, mixed $value, array $sharedLabels): string
    {
        if ($value === null || $value === '') {
            return 'No answer';
        }

        if ($item === null || ! in_array(self::type($item), ['scale', 'choice'], true)) {
            return (string) $value;
        }

        $label = self::choices($item, $sharedLabels)[$value] ?? null;

        return $label !== null ? "{$label} ({$value})" : (string) $value;
    }

    /**
     * Parse admin editor text (one "code = label" per line) into an options map.
     *
     * @return array<string, string>
     */
    public static function parseOptionsText(string $text): array
    {
        $options = [];

        foreach (preg_split('/\R/u', trim($text)) ?: [] as $lineNumber => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (! preg_match('/^(\d{1,3})\s*=\s*(.+)$/u', $line, $match)) {
                throw new \InvalidArgumentException('Answer choice line '.($lineNumber + 1)." must look like \"1 = Label\" (got \"{$line}\").");
            }

            $code = (string) (int) $match[1];
            if (array_key_exists($code, $options)) {
                throw new \InvalidArgumentException("Answer choice code {$code} is used more than once.");
            }

            $options[$code] = trim($match[2]);
        }

        if ($options === []) {
            throw new \InvalidArgumentException('Choice questions need at least one answer choice.');
        }

        return $options;
    }

    /**
     * @param  array<int|string, string>  $options
     */
    public static function optionsText(array $options): string
    {
        return collect($options)
            ->map(fn (string $label, int|string $code): string => "{$code} = {$label}")
            ->implode("\n");
    }
}
