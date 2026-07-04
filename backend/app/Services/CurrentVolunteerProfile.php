<?php

namespace App\Services;

use App\Models\User;
use App\Models\VolunteerProfile;
use Illuminate\Auth\Access\AuthorizationException;

class CurrentVolunteerProfile
{
    public function resolve(User $user): VolunteerProfile
    {
        $profile = $user->volunteerProfile;

        if (! $profile) {
            throw new AuthorizationException('User tidak memiliki profile volunteer.');
        }

        return $profile;
    }
}
