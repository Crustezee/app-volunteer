<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stats' => $this->resource['stats'],
            'categories' => CategoryResource::collection($this->resource['categories']),
            'featuredEvents' => VolunteerEventResource::collection($this->resource['featuredEvents']),
        ];
    }
}
