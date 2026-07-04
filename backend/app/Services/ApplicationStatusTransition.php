<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use Illuminate\Validation\ValidationException;

class ApplicationStatusTransition
{
    public function validate(ApplicationStatus $current, ApplicationStatus $target): void
    {
        if ($current === $target) {
            return;
        }

        $allowed = match ($current) {
            ApplicationStatus::Submitted => [
                ApplicationStatus::Accepted,
                ApplicationStatus::Waitlisted,
                ApplicationStatus::Rejected,
            ],
            ApplicationStatus::Waitlisted => [
                ApplicationStatus::Accepted,
                ApplicationStatus::Rejected,
            ],
            ApplicationStatus::Accepted => [
                ApplicationStatus::Completed,
            ],
            default => [],
        };

        if (! in_array($target, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Status tidak dapat diubah dari {$current->value} ke {$target->value}.",
            ]);
        }
    }
}
