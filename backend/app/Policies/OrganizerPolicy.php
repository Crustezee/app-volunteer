<?php

namespace App\Policies;

use App\Enums\OrganizerMemberRole;
use App\Models\Organizer;
use App\Models\User;

class OrganizerPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Organizer $organizer): bool
    {
        return true;
    }

    public function manage(User $user, Organizer $organizer): bool
    {
        return $user->hasOrganizerRole($organizer, OrganizerMemberRole::managerValues());
    }

    public function viewDashboard(User $user, Organizer $organizer): bool
    {
        return $user->belongsToOrganizer($organizer);
    }

    public function update(User $user, Organizer $organizer): bool
    {
        return $this->manage($user, $organizer);
    }

    public function delete(User $user, Organizer $organizer): bool
    {
        return $user->hasOrganizerRole($organizer, [OrganizerMemberRole::Owner]);
    }
}
