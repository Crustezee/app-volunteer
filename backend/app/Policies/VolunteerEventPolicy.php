<?php

namespace App\Policies;

use App\Enums\OrganizerMemberRole;
use App\Models\User;
use App\Models\VolunteerEvent;

class VolunteerEventPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, VolunteerEvent $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->organizerMemberships()
            ->whereIn('role', OrganizerMemberRole::managerValues())
            ->exists();
    }

    public function update(User $user, VolunteerEvent $event): bool
    {
        return $user->hasOrganizerRole($event->organizer_id, OrganizerMemberRole::managerValues());
    }

    public function delete(User $user, VolunteerEvent $event): bool
    {
        return $this->update($user, $event);
    }
}
