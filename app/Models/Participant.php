<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Participant extends Model
{
    protected $fillable = [
        'user_id',
        'treatment_track_id',
        'primary_clinician_id',
        'enrolled_at',
        'therapy_started_on',
        'case_manager_email',
        'therapy_alerts_sent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'therapy_started_on' => 'date',
            'therapy_alerts_sent' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function treatmentTrack(): BelongsTo
    {
        return $this->belongsTo(TreatmentTrack::class);
    }

    public function primaryClinician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_clinician_id');
    }

    public function assessmentResults(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }

    public function treatmentRecommendations(): HasMany
    {
        return $this->hasMany(TreatmentRecommendation::class);
    }

    public function messageThread(): HasOne
    {
        return $this->hasOne(MessageThread::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(ParticipantConsent::class);
    }

    public function latestConsent(): HasOne
    {
        return $this->hasOne(ParticipantConsent::class)->latestOfMany();
    }

    public function hasConsented(): bool
    {
        return $this->latestConsent?->decision === ParticipantConsent::AGREED;
    }
}
