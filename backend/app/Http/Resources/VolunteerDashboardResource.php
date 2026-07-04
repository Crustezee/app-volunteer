<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VolunteerDashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'profile' => new VolunteerProfileResource($this->resource['profile']),
            'stats' => $this->resource['stats'],
            'applications' => VolunteerApplicationResource::collection($this->resource['applications']),
            'certificates' => CertificateResource::collection($this->resource['certificates']),
            'savedEvents' => VolunteerEventResource::collection($this->resource['savedEvents']),
            'notifications' => NotificationResource::collection($this->resource['notifications']),
        ];
    }
}
