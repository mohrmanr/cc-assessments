<?php

namespace App\Services;

use App\Enums\AdministrationType;
use App\Models\AssessmentResult;
use App\Models\Instrument;
use App\Models\Participant;

class AssessmentFollowUp
{
    /**
     * Therapy-milestone keys that are due on or before today (UTC date).
     *
     * @return list<string>
     */
    public function dueMilestoneKeys(Participant $participant): array
    {
        $start = $participant->therapy_started_on;
        if (! $start) {
            return [];
        }

        $today = now()->toDateString();
        $due = [];

        foreach (TherapyFollowUpAlerts::MILESTONES as $key => $milestone) {
            $dueOn = $start->copy()->addMonths($milestone['months'])->toDateString();
            if ($today >= $dueOn) {
                $due[] = $key;
            }
        }

        return $due;
    }

    /**
     * Earliest due milestone that does not yet have a saved result for this instrument.
     *
     * @param  \Illuminate\Support\Collection<int, AssessmentResult>|null  $preloadedResults
     */
    public function nextOpenMilestone(Participant $participant, Instrument $instrument, $preloadedResults = null): ?string
    {
        $results = $preloadedResults ?? AssessmentResult::query()
            ->where('participant_id', $participant->id)
            ->where('instrument_id', $instrument->id)
            ->get();

        if (! $results->contains(fn (AssessmentResult $result): bool => $result->administration_type === AdministrationType::Baseline)) {
            return null;
        }

        $covered = $results
            ->pluck('milestone_key')
            ->filter()
            ->values()
            ->all();

        foreach ($this->dueMilestoneKeys($participant) as $key) {
            if (! in_array($key, $covered, true)) {
                return $key;
            }
        }

        return null;
    }

    public function baselineResult(Participant $participant, Instrument $instrument): ?AssessmentResult
    {
        return AssessmentResult::query()
            ->where('participant_id', $participant->id)
            ->where('instrument_id', $instrument->id)
            ->where('administration_type', AdministrationType::Baseline)
            ->orderBy('administered_at')
            ->first();
    }

    public function latestResult(Participant $participant, Instrument $instrument): ?AssessmentResult
    {
        return AssessmentResult::query()
            ->where('participant_id', $participant->id)
            ->where('instrument_id', $instrument->id)
            ->orderByDesc('administered_at')
            ->first();
    }

    public function milestoneLabel(string $key): string
    {
        return TherapyFollowUpAlerts::MILESTONES[$key]['label'] ?? $key;
    }
}