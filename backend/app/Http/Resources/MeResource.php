<?php

namespace App\Http\Resources;

use App\Enums\OrganizerMemberRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource),
            'volunteerProfile' => $this->when(
                $this->relationLoaded('volunteerProfile') && $this->volunteerProfile,
                fn () => new VolunteerProfileResource($this->volunteerProfile)
            ),
            'organizers' => OrganizerResource::collection($this->whenLoaded('organizers')),
            'capabilities' => [
                'volunteer' => $this->relationLoaded('volunteerProfile') && $this->volunteerProfile !== null,
                'organizer' => $this->relationLoaded('organizers') && $this->organizers->isNotEmpty(),
                'manageOrganizer' => $this->relationLoaded('organizers')
                    && $this->organizers->contains(
                        fn ($organizer) => in_array(
                            $organizer->pivot->role,
                            OrganizerMemberRole::managerValues(),
                            true
                        )
                    ),
            ],
        ];
    }
}
