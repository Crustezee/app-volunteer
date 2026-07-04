<?php

namespace App\Policies;

use App\Models\SavedEvent;
use App\Models\User;

class SavedEventPolicy
{
    public function create(User $user): bool
    {
        return $user->volunteerProfile()->exists();
    }

    public function view(User $user, SavedEvent $savedEvent): bool
    {
        return $savedEvent->volunteerProfile->user_id === $user->id;
    }

    public function update(User $user, SavedEvent $savedEvent): bool
    {
        return $this->view($user, $savedEvent);
    }

    public function delete(User $user, SavedEvent $savedEvent): bool
    {
        return $this->view($user, $savedEvent);
    }
}
