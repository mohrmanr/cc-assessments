<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePurchase extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'amount_cents',
        'is_stub',
        'purchased_at',
    ];

    protected function casts(): array
    {
        return [
            'is_stub' => 'boolean',
            'purchased_at' => 'datetime',
            'amount_cents' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
