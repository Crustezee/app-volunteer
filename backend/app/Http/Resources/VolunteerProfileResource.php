<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VolunteerProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'university' => $this->university,
            'major' => $this->major,
            'city' => $this->city,
            'avatarInitials' => $this->avatar_initials,
            'interests' => $this->interests,
            'totalHours' => $this->when(
                array_key_exists('total_hours', $this->getAttributes()),
                fn () => (int) $this->total_hours
            ),
            'completedEvents' => $this->when(
                array_key_exists('completed_events', $this->getAttributes()),
                fn () => (int) $this->completed_events
            ),
            'certificates' => $this->when(
                array_key_exists('certificates_count', $this->getAttributes()),
                fn () => (int) $this->certificates_count
            ),
            'savedEventIds' => $this->whenLoaded(
                'savedEvents',
                fn () => $this->savedEvents->pluck('event_id')->values()
            ),
        ];
    }
}
