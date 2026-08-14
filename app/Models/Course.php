<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'description',
        'price_cents',
        'requires_payment',
        'pass_percent',
        'video_placeholder',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_payment' => 'boolean',
            'is_active' => 'boolean',
            'price_cents' => 'integer',
            'pass_percent' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(CourseQuiz::class);
    }

    public function pretest(): HasOne
    {
        return $this->hasOne(CourseQuiz::class)->where('kind', 'pretest');
    }

    public function posttest(): HasOne
    {
        return $this->hasOne(CourseQuiz::class)->where('kind', 'posttest');
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(CourseAccess::class);
    }
}
