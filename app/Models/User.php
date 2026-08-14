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

    public function assignedRoles(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class);
    }

    public function courseAccesses(): HasMany
    {
        return $this->hasMany(CourseAccess::class);
    }

    /**
     * @return list<UserRole>
     */
    public function roleList(): array
    {
        $fromPivot = $this->assignedRoles
            ->pluck('role')
            ->map(fn (string $role): UserRole => UserRole::from($role))
            ->all();

        if ($fromPivot !== []) {
            return $fromPivot;
        }

        return $this->role instanceof UserRole ? [$this->role] : [];
    }

    public function hasRole(UserRole $role): bool
    {
        return in_array($role, $this->roleList(), true);
    }

    public function grantRole(UserRole $role): void
    {
        UserRoleAssignment::query()->firstOrCreate([
            'user_id' => $this->id,
            'role' => $role->value,
        ]);

        if ($this->role === null) {
            $this->forceFill(['role' => $role])->save();
        }
    }

    public function revokeRole(UserRole $role): void
    {
        UserRoleAssignment::query()
            ->where('user_id', $this->id)
            ->where('role', $role->value)
            ->delete();

        if ($this->role === $role) {
            $remaining = UserRoleAssignment::query()
                ->where('user_id', $this->id)
                ->value('role');
            if ($remaining) {
                $this->forceFill(['role' => UserRole::from($remaining)])->save();
            }
        }
    }

    public function isParticipant(): bool
    {
        return $this->hasRole(UserRole::Participant);
    }

    public function isClinician(): bool
    {
        return $this->hasRole(UserRole::Clinician);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function isClinicalSupervisor(): bool
    {
        return $this->hasRole(UserRole::ClinicalSupervisor);
    }

    public function isLearner(): bool
    {
        return $this->hasRole(UserRole::Learner);
    }

    public function preferredDashboardRoute(): string
    {
        foreach ([
            UserRole::Admin,
            UserRole::ClinicalSupervisor,
            UserRole::Clinician,
            UserRole::Participant,
            UserRole::Learner,
        ] as $role) {
            if ($this->hasRole($role)) {
                return $role->dashboardRoute();
            }
        }

        return $this->role?->dashboardRoute() ?? 'dashboard';
    }
}
