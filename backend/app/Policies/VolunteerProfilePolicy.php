<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VolunteerProfile;

class VolunteerProfilePolicy
{
    public function view(User $user, VolunteerProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    public function update(User $user, VolunteerProfile $profile): bool
    {
        return $this->view($user, $profile);
    }
}
