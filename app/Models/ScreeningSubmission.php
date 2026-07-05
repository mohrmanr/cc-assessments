<?php

namespace App\Models;

use App\Enums\ScreeningOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScreeningSubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'responses',
        'outcome',
        'safety_flag',
        'safety_notes',
        'auto_decision_reason',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'user_id',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'responses' => 'array',
            'outcome' => ScreeningOutcome::class,
            'safety_flag' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(AccountInvitation::class);
    }
}
