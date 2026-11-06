<?php

use App\Support\SurveyItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('instruments/interview.json');
        if (! File::exists($path)) {
            return;
        }

        $definition = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $slug = trim((string) ($definition['slug'] ?? ''));
        if ($slug === '') {
            return;
        }

        $items = collect($definition['items'] ?? [])
            ->map(function (array $item): array {
                $type = SurveyItem::type($item);
                $normalized = [
                    'id' => (string) $item['id'],
                    'text' => trim((string) $item['text']),
                ];
                if ($type !== 'scale') {
                    $normalized['type'] = $type;
                }
                if ($type === 'choice') {
                    $normalized['options'] = collect($item['options'] ?? [])
                        ->mapWithKeys(fn (string $label, int|string $code): array => [(string) $code => trim($label)])
                        ->all();
                }
                if (! SurveyItem::isRequired($item)) {
                    $normalized['required'] = false;
                }

                return $normalized;
            })
            ->values()
            ->all();

        $scoringConfig = [
            'method' => Arr::get($definition, 'scoring_config.method', 'none'),
            'threshold' => Arr::get($definition, 'scoring_config.threshold'),
            'direction' => Arr::get($definition, 'scoring_config.direction', 'above'),
            'response_labels' => $definition['response_labels'] ?? [],
            'score_max' => Arr::get($definition, 'scoring_config.score_max'),
            'description' => Arr::get($definition, 'description', "Complete the {$definition['name']} assessment."),
            'instructions' => Arr::get($definition, 'instructions', 'Select one answer per question.'),
            'intro' => Arr::get($definition, 'intro', []),
            'closing' => Arr::get($definition, 'closing'),
            'fields' => Arr::get($definition, 'fields', []),
            'administered_by' => Arr::get($definition, 'administered_by', 'staff'),
        ];

        $now = now();
        $payload = [
            'name' => trim((string) $definition['name']),
            'version' => trim((string) $definition['version']),
            'domain' => trim((string) $definition['domain']),
            'items' => json_encode($items),
            'scoring_config' => json_encode($scoringConfig),
            'is_active' => 1,
            'updated_at' => $now,
        ];

        $existing = DB::table('instruments')->where('slug', $slug)->first(['id']);
        if ($existing) {
            DB::table('instruments')->where('id', $existing->id)->update($payload);
        } else {
            DB::table('instruments')->insert($payload + [
                'slug' => $slug,
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $instrumentId = DB::table('instruments')->where('slug', 'interview')->value('id');
        if ($instrumentId === null) {
            return;
        }

        $resultIds = DB::table('assessment_results')->where('instrument_id', $instrumentId)->pluck('id');
        if ($resultIds->isNotEmpty()) {
            DB::table('treatment_recommendations')->whereIn('assessment_result_id', $resultIds)->delete();
            DB::table('assessment_results')->where('instrument_id', $instrumentId)->delete();
        }

        DB::table('instruments')->where('id', $instrumentId)->delete();
    }
};