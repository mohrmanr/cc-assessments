<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\ScreeningOutcome;
use App\Http\Controllers\Controller;
use App\Models\ScreeningSubmission;
use App\Services\AccountActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScreeningReviewController extends Controller
{
    public function index(): View
    {
        $pending = ScreeningSubmission::query()
            ->where('outcome', ScreeningOutcome::PendingReview)
            ->whereNull('user_id')
            ->latest()
            ->get();

        $safetyFlags = ScreeningSubmission::query()
            ->where('safety_flag', true)
            ->whereNull('user_id')
            ->latest()
            ->get();

        return view('dashboards.supervisor-review', compact('pending', 'safetyFlags'));
    }

    public function approve(ScreeningSubmission $submission, AccountActivationService $activation): RedirectResponse
    {
        $this->authorizeReview($submission);

        $submission->update([
            'outcome' => ScreeningOutcome::Eligible,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => 'Approved by clinical supervisor.',
        ]);

        if (! $submission->invitations()->exists()) {
            $invitation = $activation->createInvitation($submission->fresh());
        } else {
            $invitation = $submission->invitations()->latest()->first();
        }

        return redirect()
            ->route('screening.result', $submission)
            ->with('status', 'Approved. Share the activation link from the screening result page.');
    }

    public function decline(Request $request, ScreeningSubmission $submission): RedirectResponse
    {
        $this->authorizeReview($submission);

        $submission->update([
            'outcome' => ScreeningOutcome::NotEligible,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes', 'Declined by clinical supervisor.'),
        ]);

        return redirect()
            ->route('supervisor.screenings.index')
            ->with('status', "Declined {$submission->first_name} {$submission->last_name}.");
    }

    protected function authorizeReview(ScreeningSubmission $submission): void
    {
        if ($submission->outcome !== ScreeningOutcome::PendingReview) {
            abort(403);
        }
    }
}
