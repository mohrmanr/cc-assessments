<?php

namespace App\Models;

use App\Enums\TreatmentRecommendationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentRecommendation extends Model
{
    protected $fillable = [
        'participant_id',
        'assessment_result_id',
        'status',
        'recommended_track_id',
        'confirmed_by',
        'confirmed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => TreatmentRecommendationStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function assessmentResult(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class);
    }

    public function recommendedTrack(): BelongsTo
    {
        return $this->belongsTo(TreatmentTrack::class, 'recommended_track_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
