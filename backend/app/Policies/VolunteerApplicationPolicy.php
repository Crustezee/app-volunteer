<?php

namespace App\Policies;

use App\Enums\OrganizerMemberRole;
use App\Models\User;
use App\Models\VolunteerApplication;

class VolunteerApplicationPolicy
{
    public function view(User $user, VolunteerApplication $application): bool
    {
        if ($user->volunteerProfile?->is($application->volunteerProfile)) {
            return true;
        }

        return $user->belongsToOrganizer($application->event->organizer_id);
    }

    public function create(User $user): bool
    {
        return $user->volunteerProfile()->exists();
    }

    public function update(User $user, VolunteerApplication $application): bool
    {
        return $user->hasOrganizerRole(
            $application->event->organizer_id,
            OrganizerMemberRole::managerValues()
        );
    }
}
