<?php

namespace App\Enums;

enum ScreeningOutcome: string
{
    case InProgress = 'in_progress';
    case PendingReview = 'pending_review';
    case Eligible = 'eligible';
    case NotEligible = 'not_eligible';
}
