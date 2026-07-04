<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VolunteerEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'categoryId' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category->name),
            'organizerId' => $this->organizer_id,
            'organizer' => new OrganizerResource($this->whenLoaded('organizer')),
            'location' => $this->location,
            'city' => $this->city,
            'mode' => $this->mode->value,
            'date' => $this->date,
            'startTime' => $this->start_time,
            'endTime' => $this->end_time,
            'durationHours' => $this->duration_hours,
            'quota' => $this->quota,
            'registered' => $this->registered,
            'remainingQuota' => max($this->quota - $this->registered, 0),
            'status' => $this->status->value,
            'image' => $this->image,
            'shortDescription' => $this->short_description,
            'description' => $this->description,
            'benefits' => $this->benefits,
            'skills' => $this->skills,
            'roles' => $this->roles,
            'impactTarget' => $this->impact_target,
            'tags' => $this->tags,
            'featured' => $this->featured,
            'isSaved' => (bool) ($this->is_saved ?? false),
            'myApplication' => new VolunteerApplicationResource($this->whenLoaded('myApplication')),
            'relatedEvents' => VolunteerEventResource::collection($this->whenLoaded('relatedEvents')),
        ];
    }
}
