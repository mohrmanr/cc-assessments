<?php

namespace App\Models;

use App\Enums\CourseQuizKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseQuiz extends Model
{
    protected $fillable = [
        'course_id',
        'kind',
        'title',
        'items',
    ];

    protected function casts(): array
    {
        return [
            'kind' => CourseQuizKind::class,
            'items' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CourseQuizSubmission::class);
    }
}
