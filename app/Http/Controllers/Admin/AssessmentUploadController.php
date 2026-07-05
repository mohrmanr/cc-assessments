<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentResult;
use App\Models\Instrument;
use App\Models\Participant;
use App\Support\AttachmentQuadrantPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AssessmentUploadController extends Controller
{
    public function index(): View
    {
        $instruments = Instrument::query()
            ->orderBy('name')
            ->get();
        $completedAssessments = AssessmentResult::query()
            ->with(['participant.user', 'instrument', 'primaryClinician'])
            ->latest('administered_at')
            ->limit(100)
            ->get();

        return view('dashboards.admin', compact('instruments', 'completedAssessments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assessment_file' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:256'],
        ]);

        $json = file_get_contents($validated['assessment_file']->getRealPath());
        $definition = json_decode($json, true);

        if (! is_array($definition) || json_last_error() !== JSON_ERROR_NONE) {
            return back()
                ->withErrors(['assessment_file' => 'Upload a valid JSON assessment definition.'])
                ->withInput();
        }

        $errors = $this->validateDefinition($definition);
        if ($errors !== []) {
            return back()
                ->withErrors(['assessment_file' => implode(' ', $errors)])
                ->withInput();
        }

        $slug = Str::slug($definition['slug']);
        $items = collect($definition['items'])
            ->map(fn (array $item): array => [
                'id' => Str::snake($item['id']),
                'text' => trim($item['text']),
            ])
            ->values()
            ->all();

        $scoringConfig = [
            'method' => Arr::get($definition, 'scoring_config.method', 'sum'),
            'threshold' => Arr::get($definition, 'scoring_config.threshold'),
            'direction' => Arr::get($definition, 'scoring_config.direction', 'above'),
            'response_labels' => $definition['response_labels'],
            'score_max' => Arr::get($definition, 'scoring_config.score_max'),
            'description' => Arr::get($definition, 'description', "Complete the {$definition['name']} assessment."),
            'instructions' => Arr::get($definition, 'instructions', 'Select one answer per question.'),
            'intro' => Arr::get($definition, 'intro', []),
            'closing' => Arr::get($definition, 'closing'),
            'fields' => Arr::get($definition, 'fields', []),
        ];

        Instrument::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => trim($definition['name']),
                'version' => trim($definition['version']),
                'domain' => trim($definition['domain']),
                'items' => $items,
                'scoring_config' => $scoringConfig,
                'is_active' => true,
            ]
        );

        return redirect()
            ->route('admin.dashboard')
            ->with('status', "Assessment '{$definition['name']}' uploaded and activated.");
    }

    public function downloadCompleted(): StreamedResponse
    {
        $filename = 'completed-assessments-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'assessment_result_id',
                'participant_name',
                'participant_email',
                'instrument_slug',
                'instrument_name',
                'instrument_version',
                'administration_type',
                'total_score',
                'threshold_met',
                'primary_clinician_name',
                'primary_clinician_email',
                'administered_at',
                'reference_fields_json',
                'item_responses_json',
            ]);

            AssessmentResult::query()
                ->with(['participant.user', 'instrument', 'primaryClinician'])
                ->orderBy('administered_at')
                ->chunkById(100, function ($results) use ($handle): void {
                    foreach ($results as $result) {
                        $responses = $result->item_responses ?? [];
                        $fields = $responses['fields'] ?? [];
                        $items = $responses['items'] ?? $responses;

                        fputcsv($handle, [
                            $result->id,
                            $result->participant->user->name,
                            $result->participant->user->email,
                            $result->instrument->slug,
                            $result->instrument->name,
                            $result->instrument->version,
                            $result->administration_type->value,
                            $result->total_score,
                            $result->threshold_met ? 'yes' : 'no',
                            $result->primaryClinician?->name,
                            $result->primaryClinician?->email,
                            $result->administered_at?->toIso8601String(),
                            json_encode($fields, JSON_THROW_ON_ERROR),
                            json_encode($items, JSON_THROW_ON_ERROR),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function participantResults(Participant $participant): View
    {
        $participant->load(['user', 'assessmentResults.instrument']);

        $results = $participant->assessmentResults
            ->sortBy('administered_at')
            ->groupBy(fn (AssessmentResult $result): string => $result->instrument->version ?: $result->instrument->name);

        $ecrSeries = $results->first(
            fn ($series, string $label): bool => str($label)->contains('ECR-RS')
        );
        $attachmentQuadrantCharts = $ecrSeries instanceof \Illuminate\Support\Collection
            ? AttachmentQuadrantPresenter::chartsForSeries($ecrSeries)
            : [];

        return view('dashboards.participant-results', compact('participant', 'results', 'attachmentQuadrantCharts'));
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<int, string>
     */
    protected function validateDefinition(array $definition): array
    {
        $errors = [];

        foreach (['slug', 'name', 'version', 'domain'] as $field) {
            if (! is_string($definition[$field] ?? null) || trim($definition[$field]) === '') {
                $errors[] = "Missing required string field '{$field}'.";
            }
        }

        if (! is_array($definition['items'] ?? null) || count($definition['items']) === 0) {
            $errors[] = 'Provide at least one item.';
        } else {
            foreach ($definition['items'] as $index => $item) {
                if (! is_array($item) || ! is_string($item['id'] ?? null) || ! is_string($item['text'] ?? null)) {
                    $errors[] = 'Each item must include string id and text fields.';
                    break;
                }
                if (trim($item['id']) === '' || trim($item['text']) === '') {
                    $errors[] = 'Item ids and text cannot be blank.';
                    break;
                }
            }
        }

        if (! is_array($definition['response_labels'] ?? null) || count($definition['response_labels']) === 0) {
            $errors[] = 'Provide response_labels, for example {"0": "No", "1": "Yes"}.';
        } else {
            foreach ($definition['response_labels'] as $value => $label) {
                if (! is_numeric($value) || ! is_string($label) || trim($label) === '') {
                    $errors[] = 'response_labels must map numeric values to non-empty labels.';
                    break;
                }
            }
        }

        $method = Arr::get($definition, 'scoring_config.method', 'sum');
        if (! in_array($method, ['sum', 'mean_x100'], true)) {
            $errors[] = "scoring_config.method must be 'sum' or 'mean_x100'.";
        }

        $direction = Arr::get($definition, 'scoring_config.direction', 'above');
        if (! in_array($direction, ['above', 'below'], true)) {
            $errors[] = "scoring_config.direction must be 'above' or 'below'.";
        }

        return $errors;
    }
}
