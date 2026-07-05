<?php

namespace App\Http\Controllers;

use App\Models\AccountInvitation;
use App\Services\AccountActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountActivationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = AccountInvitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return view('portal.activate-account', [
            'invitation' => $invitation,
            'submission' => $invitation->screeningSubmission,
        ]);
    }

    public function store(Request $request, string $token, AccountActivationService $activation): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = $activation->activate($token, $validated['password']);

        Auth::login($user);

        return redirect()
            ->route('participant.dashboard')
            ->with('status', 'Account activated. Please complete your baseline PCL-5 assessment.');
    }
}
