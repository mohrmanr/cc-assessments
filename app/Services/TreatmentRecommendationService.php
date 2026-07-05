<?php

namespace App\Services;

use App\Enums\TreatmentRecommendationStatus;
use App\Models\AssessmentResult;
use App\Models\Instrument;
use App\Models\Participant;
use App\Models\TreatmentRecommendation;
use App\Models\TreatmentTrack;

class TreatmentRecommendationService
{
    public function maybeCreateFromResult(AssessmentResult $result): ?TreatmentRecommendation
    {
        if (! $result->threshold_met) {
            return null;
        }

        $participant = $result->participant;
        $track = $this->resolveTrack($result->instrument);

        if ($track && ! $participant->treatment_track_id) {
            $participant->update(['treatment_track_id' => $track->id]);
        }

        $clinician = $this->assignClinician($participant, $track);

        if ($clinician) {
            $participant->update(['primary_clinician_id' => $clinician->id]);
            $result->update(['primary_clinician_id' => $clinician->id]);
        }

        return TreatmentRecommendation::query()->create([
            'participant_id' => $participant->id,
            'assessment_result_id' => $result->id,
            'status' => TreatmentRecommendationStatus::Pending,
            'recommended_track_id' => $track?->id,
        ]);
    }

    protected function resolveTrack(Instrument $instrument): ?TreatmentTrack
    {
        if ($instrument->domain === 'ptsd') {
            return TreatmentTrack::query()->where('slug', 'ptsd')->first();
        }

        return TreatmentTrack::query()->where('slug', 'general')->first();
    }

    protected function assignClinician(Participant $participant, ?TreatmentTrack $track)
    {
        if (! $track) {
            return null;
        }

        $clinician = $track->clinicians()->first();

        return $clinician ?? $participant->primaryClinician;
    }
}
