<?php

namespace App\Http\Controllers\Clinician;

use App\Http\Controllers\Controller;
use App\Enums\TreatmentRecommendationStatus;
use App\Models\Participant;
use App\Models\TreatmentRecommendation;
use Illuminate\View\View;

class CaseloadController extends Controller
{
    public function index(): View
    {
        $clinicianId = auth()->id();

        $participants = Participant::query()
            ->with(['user', 'treatmentTrack', 'assessmentResults.instrument'])
            ->where('primary_clinician_id', $clinicianId)
            ->latest('enrolled_at')
            ->get();

        $recommendations = TreatmentRecommendation::query()
            ->with(['participant.user', 'assessmentResult.instrument', 'recommendedTrack'])
            ->whereHas('participant', fn ($q) => $q->where('primary_clinician_id', $clinicianId))
            ->where('status', TreatmentRecommendationStatus::Pending)
            ->latest()
            ->get();

        return view('dashboards.clinician-caseload', compact('participants', 'recommendations'));
    }

    public function confirmRecommendation(TreatmentRecommendation $recommendation): \Illuminate\Http\RedirectResponse
    {
        if ($recommendation->participant->primary_clinician_id !== auth()->id()) {
            abort(403);
        }

        $recommendation->update([
            'status' => TreatmentRecommendationStatus::Confirmed,
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
            'notes' => 'Confirmed during demo review.',
        ]);

        return redirect()
            ->route('clinician.dashboard')
            ->with('status', 'Treatment recommendation confirmed.');
    }
}
