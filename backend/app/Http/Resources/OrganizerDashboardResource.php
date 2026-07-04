<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerDashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'organizer' => new OrganizerResource($this->resource['organizer']),
            'metrics' => $this->resource['metrics'],
            'events' => VolunteerEventResource::collection($this->resource['events']),
            'applications' => VolunteerApplicationResource::collection($this->resource['applications']),
        ];
    }
}
