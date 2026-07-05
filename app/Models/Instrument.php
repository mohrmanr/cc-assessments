<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instrument extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'version',
        'domain',
        'items',
        'scoring_config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'scoring_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function assessmentResults(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }
}
