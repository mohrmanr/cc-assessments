<?php

namespace App\Models;

use App\Enums\AdministrationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentResult extends Model
{
    protected $fillable = [
        'participant_id',
        'instrument_id',
        'administration_type',
        'total_score',
        'subscale_scores',
        'item_responses',
        'treatment_track_id',
        'primary_clinician_id',
        'threshold_met',
        'threshold_flags',
        'administered_at',
    ];

    protected function casts(): array
    {
        return [
            'administration_type' => AdministrationType::class,
            'subscale_scores' => 'array',
            'item_responses' => 'array',
            'threshold_met' => 'boolean',
            'threshold_flags' => 'array',
            'administered_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function treatmentTrack(): BelongsTo
    {
        return $this->belongsTo(TreatmentTrack::class);
    }

    public function primaryClinician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_clinician_id');
    }

    public function treatmentRecommendations(): HasMany
    {
        return $this->hasMany(TreatmentRecommendation::class);
    }
}
