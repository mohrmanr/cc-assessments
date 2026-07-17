<?php

namespace App\Http\Controllers\Participant;

use App\Enums\AdministrationType;
use App\Http\Controllers\Controller;
use App\Models\AssessmentResult;
use App\Models\Instrument;
use Illuminate\View\View;

class ParticipantDashboardController extends Controller
{
    public function index(): View
    {
        $participant = auth()->user()->participantProfile;
        $surveyConfigs = collect(config('portal.baseline_surveys'));
        $instruments = Instrument::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('slug');
        $dynamicSlugs = $instruments
            ->filter(fn (Instrument $instrument): bool => $this->canRenderUploadedAssessment($instrument))
            ->keys();
        $instrumentSlugs = $surveyConfigs
            ->keys()
            ->merge($dynamicSlugs)
            ->unique()
            ->values();

        $baselineResults = collect();
        if ($participant && $instruments->isNotEmpty()) {
            $baselineResults = AssessmentResult::query()
                ->where('participant_id', $participant->id)
                ->whereIn('instrument_id', $instruments->pluck('id'))
                ->where('administration_type', AdministrationType::Baseline)
                ->with('instrument')
                ->get()
                ->keyBy(fn (AssessmentResult $result) => $result->instrument->slug);
        }

        $baselineSurveys = $instrumentSlugs
            ->map(function (string $slug) use ($baselineResults, $instruments, $surveyConfigs): array {
                $instrument = $instruments->get($slug);
                $survey = $surveyConfigs->get($slug) ?? $this->surveyFromInstrument($instrument);
                $result = $baselineResults->get($slug);

                return [
                    ...$survey,
                    'slug' => $slug,
                    'instrument' => $instrument,
                    'result' => $result,
                    'completed' => $result !== null,
                ];
            })
            ->values();

        $baselineDone = $baselineSurveys->every(fn (array $survey): bool => $survey['completed']);

        $latestResult = $participant
            ? AssessmentResult::query()
                ->where('participant_id', $participant->id)
                ->with('instrument')
                ->latest('administered_at')
                ->first()
            : null;

        return view('dashboards.participant', compact('participant', 'baselineDone', 'baselineSurveys', 'latestResult'));
    }

    protected function canRenderUploadedAssessment(Instrument $instrument): bool
    {
        $config = $instrument->scoring_config ?? [];

        return ! empty($instrument->items)
            && is_array($config['response_labels'] ?? null)
            && $config['response_labels'] !== [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function surveyFromInstrument(?Instrument $instrument): array
    {
        abort_if(! $instrument, 404);

        $config = $instrument->scoring_config ?? [];
        $portalSurvey = config("portal.baseline_surveys.{$instrument->slug}", []);

        return [
            'route_name' => 'participant.assessments.show',
            'store_route_name' => 'participant.assessments.store',
            'route_params' => ['instrument' => $instrument],
            'title' => "{$instrument->name} Baseline Assessment",
            'label' => $instrument->version ?: $instrument->name,
            'description' => filled($config['description'] ?? null)
                ? $config['description']
                : ($portalSurvey['description'] ?? "Complete the {$instrument->name} assessment."),
            'score_max' => $config['score_max'] ?? null,
        ];
    }
}
