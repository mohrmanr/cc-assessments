<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentTrack extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'reassessment_schedule',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'reassessment_schedule' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function clinicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'clinician_treatment_track');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }
}
