<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipantConsented
{
    public function handle(Request $request, Closure $next): Response
    {
        $participant = $request->user()?->participantProfile;

        if ($participant && ! $participant->hasConsented()) {
            return redirect()
                ->route('participant.consent.show')
                ->with('status', 'Please read and respond to the informed consent form before starting your surveys.');
        }

        return $next($request);
    }
}
