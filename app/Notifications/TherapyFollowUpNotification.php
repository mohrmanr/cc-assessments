<?php

namespace App\Notifications;

use App\Models\Participant;
use App\Services\TherapyFollowUpAlerts;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TherapyFollowUpNotification extends Notification
{
    public function __construct(
        public readonly Participant $participant,
        public readonly string $milestoneKey,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ["mail"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->participant->loadMissing("user");
        $user = $this->participant->user;
        $label = TherapyFollowUpAlerts::MILESTONES[$this->milestoneKey]["label"];
        $started = $this->participant->therapy_started_on?->format("M j, Y");

        return (new MailMessage)
            ->subject("{$label} follow-up due: {$user->name} (User ID {$user->id})")
            ->greeting("{$label} follow-up")
            ->line("{$user->name} (User ID {$user->id}, {$user->email}) started therapy on {$started}.")
            ->line("The {$label} follow-up questionnaires are due.")
            ->action("Open admin dashboard", route("admin.dashboard"));
    }
}
