<?php

namespace App\Policies;

use App\Enums\OrganizerMemberRole;
use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function view(User $user, Certificate $certificate): bool
    {
        return $certificate->application->volunteerProfile->user_id === $user->id;
    }

    public function create(User $user, Certificate $certificate): bool
    {
        return $user->hasOrganizerRole(
            $certificate->application->event->organizer_id,
            OrganizerMemberRole::managerValues()
        );
    }
}
