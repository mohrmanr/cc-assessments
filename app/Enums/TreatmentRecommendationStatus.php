<?php

namespace App\Enums;

enum TreatmentRecommendationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
}
