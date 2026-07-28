<?php

namespace App\Support;

class AttachmentSurvey
{
    /** @var array<string, string> */
    public const TARGETS = [
        'mother' => 'Mother',
        'father' => 'Father',
        'partner' => 'Partner',
        'best_friend' => 'Best Friend',
    ];

    /**
     * Canonical ECR-RS item stems (scored once per target).
     *
     * @return array<int, array{id: string, text: string, subscale: string, reverse_score: bool}>
     */
    public static function ecrRsBaseItems(): array
    {
        return [
            [
                'id' => '1',
                'text' => 'It helps to turn to this person in times of need.',
                'subscale' => 'avoidance',
                'reverse_score' => true,
            ],
            [
                'id' => '2',
                'text' => 'I usually discuss my problems and concerns with this person.',
                'subscale' => 'avoidance',
                'reverse_score' => true,
            ],
            [
                'id' => '3',
                'text' => 'I talk things over with this person.',
                'subscale' => 'avoidance',
                'reverse_score' => true,
            ],
            [
                'id' => '4',
                'text' => 'I find it easy to depend on this person.',
                'subscale' => 'avoidance',
                'reverse_score' => true,
            ],
            [
                'id' => '5',
                'text' => 'I do not feel comfortable opening up to this person.',
                'subscale' => 'avoidance',
                'reverse_score' => false,
            ],
            [
                'id' => '6',
                'text' => 'I prefer not to show this person how I feel deep down.',
                'subscale' => 'avoidance',
                'reverse_score' => false,
            ],
            [
                'id' => '7',
                'text' => 'I often worry that this person does not really care for me.',
                'subscale' => 'anxiety',
                'reverse_score' => false,
            ],
            [
                'id' => '8',
                'text' => 'I am afraid that this person may abandon me.',
                'subscale' => 'anxiety',
                'reverse_score' => false,
            ],
            [
                'id' => '9',
                'text' => 'I worry that this person will not care about me as much as I care about them.',
                'subscale' => 'anxiety',
                'reverse_score' => false,
            ],
        ];
    }

    /**
     * Expand base items across mother / father / partner / best friend.
     *
     * @param  array<int, array<string, mixed>>  $baseItems
     * @param  array<int, string>|null  $targetKeys
     * @return array<int, array<string, mixed>>
     */
    public static function expandForTargets(array $baseItems, ?array $targetKeys = null): array
    {
        $targetKeys ??= array_keys(self::TARGETS);
        $expanded = [];

        foreach ($targetKeys as $targetKey) {
            $targetKey = (string) $targetKey;
            foreach ($baseItems as $item) {
                $baseId = (string) ($item['id'] ?? '');
                $expanded[] = array_merge($item, [
                    'id' => $targetKey.'_'.$baseId,
                    'target' => $targetKey,
                ]);
            }
        }

        return $expanded;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function ensureTargetItems(array $items, array $scoringConfig = []): array
    {
        if ($items === []) {
            return $items;
        }

        $hasTargets = collect($items)->contains(
            fn (array $item): bool => filled($item['target'] ?? null)
        );

        if ($hasTargets) {
            return $items;
        }

        $repeatFor = $scoringConfig['repeat_for_targets'] ?? null;
        if (! is_array($repeatFor) || $repeatFor === []) {
            return $items;
        }

        return self::expandForTargets($items, $repeatFor);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function groupByTarget(array $items): array
    {
        $grouped = [];
        $order = array_keys(self::TARGETS);

        foreach ($items as $item) {
            $target = (string) ($item['target'] ?? 'general');
            $grouped[$target][] = $item;
        }

        uksort($grouped, function (string $a, string $b) use ($order): int {
            $ai = array_search($a, $order, true);
            $bi = array_search($b, $order, true);
            $ai = $ai === false ? PHP_INT_MAX : $ai;
            $bi = $bi === false ? PHP_INT_MAX : $bi;

            return $ai <=> $bi ?: strcmp($a, $b);
        });

        return $grouped;
    }

    public static function targetLabel(string $targetKey): string
    {
        return self::TARGETS[$targetKey]
            ?? str($targetKey)->replace('_', ' ')->title()->toString();
    }
}
