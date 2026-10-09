<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\ParticipantConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsentController extends Controller
{
    public function show(Request $request): View
    {
        $participant = $this->participant($request);

        return view('participant.consent', [
            'participant' => $participant,
            'consent' => $participant->latestConsent,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $participant = $this->participant($request);
        $validated = $request->validate([
            'decision' => ['required', 'in:yes,no'],
        ]);

        if ($participant->hasConsented()) {
            return redirect()
                ->route('participant.consent.show')
                ->with('status', 'Your consent is already recorded. To withdraw, contact the Principal Investigator.');
        }

        $consent = $participant->consents()->create([
            'decision' => $validated['decision'] === 'yes' ? ParticipantConsent::AGREED : ParticipantConsent::DECLINED,
            'consent_version' => config('portal.consent_version'),
            'signed_name' => $request->user()->name,
            'ip_address' => $request->ip(),
        ]);
        $participant->setRelation('latestConsent', $consent);

        $message = $consent->decision === ParticipantConsent::AGREED
            ? 'Thank you. Your informed consent was recorded on '.$consent->created_at->format('M j, Y').'. You can now start your surveys.'
            : 'Your decision not to participate in the research was recorded. Your surveys will stay locked.';

        return redirect()
            ->route('participant.dashboard')
            ->with('status', $message);
    }

    protected function participant(Request $request): Participant
    {
        $participant = $request->user()->participantProfile;
        abort_unless($participant, 403);

        return $participant;
    }
}
