<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountInvitation extends Model
{
    protected $fillable = [
        'screening_submission_id',
        'token',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function screeningSubmission(): BelongsTo
    {
        return $this->belongsTo(ScreeningSubmission::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
