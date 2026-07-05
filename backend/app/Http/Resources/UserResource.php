<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'emailVerifiedAt' => $this->email_verified_at?->toISOString(),
            'role' => $this->role ?? 'volunteer',
            'status' => $this->status ?? 'Active',
            'city' => $this->city,
            'avatarInitials' => $this->avatar_initials,
        ];
    }
}
