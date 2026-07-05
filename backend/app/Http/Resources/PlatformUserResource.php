<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $profile = $user->relationLoaded('volunteerProfile') ? $user->volunteerProfile : null;
        $organizer = $user->relationLoaded('organizers') ? $user->organizers->first() : null;
        $role = $this->role($user);
        $name = $role === 'organizer' && $organizer ? $organizer->name : $user->name;
        $city = $user->city ?? $profile?->city ?? $organizer?->city ?? '-';

        return [
            'id' => $profile?->id ?? 'usr-'.$user->id,
            'name' => $name,
            'email' => $user->email,
            'role' => $role,
            'status' => $user->status ?? 'Active',
            'city' => $city,
            'joinedAt' => $user->created_at?->toDateString(),
            'avatarInitials' => $user->avatar_initials ?? $profile?->avatar_initials ?? $this->initials($name),
        ];
    }

    private function role(User $user): string
    {
        if ($user->role === 'admin') {
            return 'admin';
        }

        if ($user->role === 'organizer' || ($user->relationLoaded('organizers') && $user->organizers->isNotEmpty())) {
            return 'organizer';
        }

        return 'volunteer';
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->implode('') ?: 'U';
    }
}
