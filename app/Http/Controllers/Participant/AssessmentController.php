<?php

namespace App\Http\Controllers\Participant;

use App\Enums\AdministrationType;
use App\Http\Controllers\Controller;
use App\Models\AssessmentResult;
use App\Models\Instrument;
use App\Models\User;
use App\Notifications\AssessmentCompletedNotification;
use App\Services\InstrumentScorer;
use App\Services\TreatmentRecommendationService;
use App\Support\SurveyScaleConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function show(Instrument $instrument): View|RedirectResponse
    {
        return $this->showBaselineSurvey($instrument->slug);
    }

    public function store(Request $request, Instrument $instrument, InstrumentScorer $scorer, TreatmentRecommendationService $recommendations): RedirectResponse
    {
        return $this->storeBaselineSurvey($request, $scorer, $recommendations, $instrument->slug);
    }

    public function showPcl5(): View|RedirectResponse
    {
        return $this->showBaselineSurvey('pcl-5');
    }

    public function storePcl5(Request $request, InstrumentScorer $scorer, TreatmentRecommendationService $recommendations): RedirectResponse
    {
        return $this->storeBaselineSurvey($request, $scorer, $recommendations, 'pcl-5');
    }

    public function showAce(): View|RedirectResponse
    {
        return $this->showBaselineSurvey('ace');
    }

    public function storeAce(Request $request, InstrumentScorer $scorer, TreatmentRecommendationService $recommendations): RedirectResponse
    {
        return $this->storeBaselineSurvey($request, $scorer, $recommendations, 'ace');
    }

    public function showDesIi(): View|RedirectResponse
    {
        return $this->showBaselineSurvey('des-ii');
    }

    public function storeDesIi(Request $request, InstrumentScorer $scorer, TreatmentRecommendationService $recommendations): RedirectResponse
    {
        return $this->storeBaselineSurvey($request, $scorer, $recommendations, 'des-ii');
    }

    protected function showBaselineSurvey(string $slug): View|RedirectResponse
    {
        $participant = auth()->user()->participantProfile;

        if (! $participant) {
            abort(403);
        }

        $survey = $this->baselineSurvey($slug);
        $instrument = Instrument::query()->where('slug', $slug)->firstOrFail();
        [$items, $labels] = $this->resolveSurveyContent($instrument, $survey);

        $existing = AssessmentResult::query()
            ->where('participant_id', $participant->id)
            ->where('instrument_id', $instrument->id)
            ->where('administration_type', AdministrationType::Baseline)
            ->exists();

        if ($existing) {
            return redirect()
                ->route('participant.dashboard')
                ->with('status', "You have already completed the baseline {$survey['label']}.");
        }

        return view('assessments.survey', [
            'survey' => $this->withSurveyRouteParams(
                $this->mergeInstrumentSurveyCopy($survey, $instrument),
                $instrument
            ),
            'instrument' => $instrument,
            'items' => $items,
            'labels' => $labels,
        ]);
    }

    protected function storeBaselineSurvey(Request $request, InstrumentScorer $scorer, TreatmentRecommendationService $recommendations, string $slug): RedirectResponse
    {
        $participant = auth()->user()->participantProfile;

        if (! $participant) {
            abort(403);
        }

        $survey = $this->baselineSurvey($slug);
        $instrument = Instrument::query()->where('slug', $slug)->firstOrFail();
        [$items, $labels] = $this->resolveSurveyContent($instrument, $survey);
        $itemIds = collect($items)->pluck('id')->all();
        $fields = $survey['fields'] ?? [];
        $usesContinuousScale = SurveyScaleConfig::usesContinuousScale($survey, $instrument->scoring_config ?? []);
        $allowedValues = array_map('intval', array_keys($labels));

        $rules = [];
        foreach ($fields as $field) {
            $fieldRules = ['required', 'string'];
            if (($field['type'] ?? 'text') === 'email') {
                $fieldRules[] = 'email';
            }
            if (isset($field['max'])) {
                $fieldRules[] = 'max:'.$field['max'];
            }
            $rules[$field['id']] = $fieldRules;
        }
        foreach ($itemIds as $id) {
            if ($usesContinuousScale) {
                $scale = SurveyScaleConfig::resolve($instrument->scoring_config ?? [], $survey);
                $min = $scale['min'];
                $max = $scale['max'];
                $rules[$id] = ['required', 'integer', 'min:'.$min, 'max:'.$max];
            } else {
                $rules[$id] = ['required', 'integer', Rule::in($allowedValues)];
            }
        }
        $validated = $request->validate($rules);
        $itemResponses = [];
        foreach ($itemIds as $id) {
            $itemResponses[$id] = (int) $validated[$id];
        }
        $fieldResponses = array_diff_key($validated, array_flip($itemIds));

        $score = $scorer->score($instrument, $itemResponses);

        $result = AssessmentResult::query()->create([
            'participant_id' => $participant->id,
            'instrument_id' => $instrument->id,
            'administration_type' => AdministrationType::Baseline,
            'total_score' => $score['total'],
            'item_responses' => [
                'fields' => $fieldResponses,
                'items' => $itemResponses,
            ],
            'treatment_track_id' => $participant->treatment_track_id,
            'primary_clinician_id' => $participant->primary_clinician_id,
            'threshold_met' => $score['threshold_met'],
            'threshold_flags' => $score['threshold_met'] ? [$instrument->slug => true] : [],
            'administered_at' => now(),
        ]);

        $recommendation = $recommendations->maybeCreateFromResult($result);
        $this->notifyAdmins($result);

        $message = $score['threshold_met']
            ? "Baseline {$survey['label']} complete. Your score suggests additional clinician review may be appropriate."
            : "Baseline {$survey['label']} complete. Your responses have been recorded.";

        if ($recommendation) {
            $message .= ' A treatment recommendation has been sent to your care team.';
        }

        return redirect()
            ->route('participant.dashboard')
            ->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    protected function baselineSurvey(string $slug): array
    {
        $survey = config("portal.baseline_surveys.{$slug}");

        if ($survey) {
            return $survey;
        }

        $instrument = Instrument::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $scoringConfig = $instrument->scoring_config ?? [];
        $labels = $scoringConfig['response_labels'] ?? null;

        abort_if(! is_array($labels) || $labels === [], 404);

        return [
            'route_name' => 'participant.assessments.show',
            'store_route_name' => 'participant.assessments.store',
            'route_params' => ['instrument' => $instrument],
            'title' => "{$instrument->name} Baseline Assessment",
            'label' => $instrument->version ?: $instrument->name,
            'description' => filled($scoringConfig['description'] ?? null)
                ? $scoringConfig['description']
                : "Complete the {$instrument->name} assessment.",
            'instructions' => filled($scoringConfig['instructions'] ?? null)
                ? $scoringConfig['instructions']
                : 'Select one answer per question.',
            'intro' => $scoringConfig['intro'] ?? [],
            'closing' => $scoringConfig['closing'] ?? null,
            'fields' => $scoringConfig['fields'] ?? [],
            'items' => $instrument->items ?? [],
            'response_labels' => $labels,
            'default' => $scoringConfig['default'] ?? null,
            'score_max' => $scoringConfig['score_max'] ?? null,
            'scale_type' => $scoringConfig['scale_type'] ?? 'discrete',
            'min' => $scoringConfig['min'] ?? 0,
            'max' => $scoringConfig['max'] ?? 100,
            'step' => $scoringConfig['step'] ?? 1,
            'scale_labels' => $scoringConfig['scale_labels'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $survey
     * @return array{0: array<int, array{id: string, text: string}>, 1: array<int|string, string>}
     */
    protected function resolveSurveyContent(Instrument $instrument, array $survey): array
    {
        $itemsKey = $survey['items_key'] ?? null;
        $items = ! empty($instrument->items)
            ? $instrument->items
            : ($survey['items'] ?? (is_string($itemsKey) ? config("portal.{$itemsKey}", []) : []));

        $scoringConfig = $instrument->scoring_config ?? [];
        $labels = $scoringConfig['response_labels'] ?? null;

        if (! is_array($labels) || $labels === []) {
            if (SurveyScaleConfig::usesContinuousScale($survey, $scoringConfig)) {
                $labels = [];
            } else {
                $labelsKey = $survey['labels_key'] ?? null;
                $labels = $survey['response_labels']
                    ?? (is_string($labelsKey) ? config("portal.{$labelsKey}", []) : []);
            }
        }

        if (
            ! SurveyScaleConfig::usesContinuousScale($survey, $scoringConfig)
            && ($scoringConfig['scale_mode'] ?? null) === 'buckets'
            && ($labels === [] || $labels === null)
        ) {
            $labels = SurveyScaleConfig::generateBucketLabels(
                (int) ($scoringConfig['bucket_min'] ?? 0),
                (int) ($scoringConfig['bucket_max'] ?? 100),
                (int) ($scoringConfig['bucket_count'] ?? 5),
                (string) ($scoringConfig['bucket_label_suffix'] ?? '')
            );
        }

        return [$items, is_array($labels) ? $labels : []];
    }

    /**
     * @param  array<string, mixed>  $survey
     * @return array<string, mixed>
     */
    protected function mergeInstrumentSurveyCopy(array $survey, Instrument $instrument): array
    {
        $config = $instrument->scoring_config ?? [];

        if (filled($config['instructions'] ?? null)) {
            $survey['instructions'] = $config['instructions'];
        }

        if (filled($config['description'] ?? null)) {
            $survey['description'] = $config['description'];
        }

        foreach (['scale_type', 'scale_mode', 'min', 'max', 'step', 'scale_labels', 'bucket_min', 'bucket_max', 'bucket_count', 'bucket_label_suffix'] as $key) {
            if (array_key_exists($key, $config) && $config[$key] !== null && $config[$key] !== []) {
                $survey[$key] = $config[$key];
            }
        }

        return $survey;
    }

    /**
     * @param  array<string, mixed>  $survey
     * @return array<string, mixed>
     */
    protected function withSurveyRouteParams(array $survey, Instrument $instrument): array
    {
        if (($survey['store_route_name'] ?? null) === 'participant.assessments.store') {
            $survey['route_params'] = ['instrument' => $instrument];
        }

        if (($survey['route_name'] ?? null) === 'participant.assessments.show') {
            $survey['route_params'] = ['instrument' => $instrument];
        }

        return $survey;
    }

    protected function notifyAdmins(AssessmentResult $result): void
    {
        $adminEmails = config('portal.admin_notification_emails', []);

        $admins = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->when($adminEmails, fn ($query) => $query->whereIn('email', $adminEmails))
            ->get();

        Notification::send($admins, new AssessmentCompletedNotification($result));
    }
}
