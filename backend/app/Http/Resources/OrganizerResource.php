<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'city' => $this->city,
            'verified' => $this->verified,
            'logoInitial' => $this->logo_initial,
            'rating' => $this->rating,
            'totalEvents' => $this->total_events,
            'responseTime' => $this->response_time,
            'memberRole' => $this->whenPivotLoaded(
                'organizer_members',
                fn () => $this->pivot->role
            ),
        ];
    }
}
