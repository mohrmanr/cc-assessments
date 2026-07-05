<?php

namespace App\Services;

use App\Enums\ScreeningOutcome;
use App\Enums\UserRole;
use App\Models\AccountInvitation;
use App\Models\Participant;
use App\Models\ScreeningSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountActivationService
{
    public function createInvitation(ScreeningSubmission $submission): AccountInvitation
    {
        return AccountInvitation::query()->create([
            'screening_submission_id' => $submission->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(3),
        ]);
    }

    public function activate(string $token, string $password): User
    {
        $invitation = AccountInvitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $submission = $invitation->screeningSubmission;

        if ($submission->outcome !== ScreeningOutcome::Eligible) {
            abort(403, 'This screening is not approved for account activation.');
        }

        if ($submission->user_id) {
            abort(403, 'An account has already been activated for this screening.');
        }

        $user = User::query()->create([
            'name' => trim($submission->first_name.' '.$submission->last_name),
            'email' => $submission->email,
            'password' => Hash::make($password),
            'role' => UserRole::Participant,
            'email_verified_at' => now(),
        ]);

        Participant::query()->create([
            'user_id' => $user->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $submission->update(['user_id' => $user->id]);
        $invitation->update(['accepted_at' => now()]);

        return $user;
    }
}
