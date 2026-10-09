<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One Yes/No answer to the research informed consent form. Rows are never updated;
 * the latest row is the participant's current decision.
 */
class ParticipantConsent extends Model
{
    public const AGREED = 'agreed';

    public const DECLINED = 'declined';

    protected $fillable = [
        'participant_id',
        'decision',
        'consent_version',
        'signed_name',
        'ip_address',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
