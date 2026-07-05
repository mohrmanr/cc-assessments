<?php

namespace App\Enums;

enum UserRole: string
{
    case Participant = 'participant';
    case Clinician = 'clinician';
    case Admin = 'admin';
    case ClinicalSupervisor = 'clinical_supervisor';

    public function label(): string
    {
        return match ($this) {
            self::Participant => 'Participant',
            self::Clinician => 'Clinician',
            self::Admin => 'Admin',
            self::ClinicalSupervisor => 'Clinical Supervisor',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Participant => 'participant.dashboard',
            self::Clinician => 'clinician.dashboard',
            self::Admin => 'admin.dashboard',
            self::ClinicalSupervisor => 'supervisor.dashboard',
        };
    }
}
