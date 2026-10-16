<?php

namespace App\Services;

use App\Models\Participant;
use App\Notifications\TherapyFollowUpNotification;
use Illuminate\Support\Facades\Notification;

class TherapyFollowUpAlerts
{
    /** @var array<string, array{months: int, label: string}> */
    public const MILESTONES = [
        "3_months" => ["months" => 3, "label" => "3-month"],
        "6_months" => ["months" => 6, "label" => "6-month"],
        "1_year" => ["months" => 12, "label" => "1-year"],
    ];

    /**
     * @return array<int, string>
     */
    public function dueKeys(Participant $participant): array
    {
        $start = $participant->therapy_started_on;
        if (! $start || ! filled($participant->case_manager_email)) {
            return [];
        }

        $today = now()->toDateString();
        $sent = $participant->therapy_alerts_sent ?? [];
        $due = [];

        foreach (self::MILESTONES as $key => $milestone) {
            if (isset($sent[$key])) {
                continue;
            }
            $dueOn = $start->copy()->addMonths($milestone["months"])->toDateString();
            if ($today >= $dueOn) {
                $due[] = $key;
            }
        }

        return $due;
    }

    public function sendDue(): int
    {
        $count = 0;

        Participant::query()
            ->whereNotNull("therapy_started_on")
            ->whereNotNull("case_manager_email")
            ->with("user")
            ->orderBy("id")
            ->each(function (Participant $participant) use (&$count): void {
                foreach ($this->dueKeys($participant) as $key) {
                    Notification::route("mail", $participant->case_manager_email)
                        ->notify(new TherapyFollowUpNotification($participant, $key));

                    $sent = $participant->therapy_alerts_sent ?? [];
                    $sent[$key] = now()->toIso8601String();
                    $participant->therapy_alerts_sent = $sent;
                    $participant->save();
                    $count++;
                }
            });

        return $count;
    }
}
