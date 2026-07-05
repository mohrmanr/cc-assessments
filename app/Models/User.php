<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function participantProfile(): HasOne
    {
        return $this->hasOne(Participant::class);
    }

    public function treatmentTracks(): BelongsToMany
    {
        return $this->belongsToMany(TreatmentTrack::class, 'clinician_treatment_track');
    }

    public function primaryParticipants(): HasMany
    {
        return $this->hasMany(Participant::class, 'primary_clinician_id');
    }

    public function clinicianThreads(): HasMany
    {
        return $this->hasMany(MessageThread::class, 'clinician_id');
    }

    public function isParticipant(): bool
    {
        return $this->role === UserRole::Participant;
    }

    public function isClinician(): bool
    {
        return $this->role === UserRole::Clinician;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isClinicalSupervisor(): bool
    {
        return $this->role === UserRole::ClinicalSupervisor;
    }
}
