<?php

namespace App\Models;

use App\Enums\CourseQuizKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseQuizSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'course_quiz_id',
        'kind',
        'answers',
        'score',
        'passed',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => CourseQuizKind::class,
            'answers' => 'array',
            'score' => 'float',
            'passed' => 'boolean',
            'submitted_at' => 'datetime',
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

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(CourseQuiz::class, 'course_quiz_id');
    }
}
