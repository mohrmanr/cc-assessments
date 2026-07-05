<?php

namespace App\Http\Controllers;

use App\Enums\ScreeningOutcome;
use App\Models\ScreeningSubmission;
use App\Services\AccountActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScreeningController extends Controller
{
    public function create(): View
    {
        return view('portal.screening-form');
    }

    public function store(Request $request, AccountActivationService $activation): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $submission = ScreeningSubmission::query()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'responses' => [],
            'outcome' => ScreeningOutcome::Eligible,
            'auto_decision_reason' => 'Your contact details have been saved. Continue by activating your account.',
        ]);

        $activation->createInvitation($submission->fresh());

        return redirect()->route('screening.result', $submission);
    }

    public function result(ScreeningSubmission $submission): View
    {
        $invitation = $submission->invitations()->latest()->first();

        return view('portal.screening-result', [
            'submission' => $submission,
            'invitation' => $invitation,
        ]);
    }
}
