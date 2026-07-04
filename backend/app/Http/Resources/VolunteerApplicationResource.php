<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VolunteerApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eventId' => $this->event_id,
            'volunteerProfileId' => $this->volunteer_profile_id,
            'role' => $this->role,
            'status' => $this->status->value,
            'submittedAt' => $this->submitted_at,
            'motivation' => $this->motivation,
            'availability' => $this->availability,
            'event' => new VolunteerEventResource($this->whenLoaded('event')),
            'volunteerProfile' => new VolunteerProfileResource($this->whenLoaded('volunteerProfile')),
            'certificates' => CertificateResource::collection($this->whenLoaded('certificates')),
        ];
    }
}
