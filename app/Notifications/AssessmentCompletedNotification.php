<?php

namespace App\Notifications;

use App\Models\AssessmentResult;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssessmentCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly AssessmentResult $result
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->result->loadMissing(['participant.user', 'instrument', 'primaryClinician']);

        $participant = $this->result->participant->user;
        $instrument = $this->result->instrument;
        $clinician = $this->result->primaryClinician;

        return (new MailMessage)
            ->subject("Assessment completed: {$participant->name}")
            ->greeting('Assessment completed')
            ->line("Participant: {$participant->name} ({$participant->email})")
            ->line("Assessment: {$instrument->name}")
            ->line("Score: {$this->result->total_score}")
            ->line('Threshold met: '.($this->result->threshold_met ? 'Yes' : 'No'))
            ->line('Primary clinician: '.($clinician?->name ?? 'Unassigned'))
            ->action('Open admin dashboard', route('admin.dashboard'));
    }
}
