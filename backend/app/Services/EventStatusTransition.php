<?php

namespace App\Services;

use App\Enums\EventStatus;
use Illuminate\Validation\ValidationException;

class EventStatusTransition
{
    public function validate(EventStatus $current, EventStatus $target): void
    {
        if ($current === $target) {
            return;
        }

        $allowed = match ($current) {
            EventStatus::Open, EventStatus::NearlyFull => [
                EventStatus::Closed,
                EventStatus::Cancelled,
                EventStatus::Completed,
            ],
            EventStatus::Closed => [
                EventStatus::Open,
                EventStatus::Cancelled,
                EventStatus::Completed,
            ],
            default => [],
        };

        if (! in_array($target, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Status event tidak dapat diubah dari {$current->value} ke {$target->value}.",
            ]);
        }
    }
}
