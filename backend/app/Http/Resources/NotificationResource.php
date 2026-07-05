<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->data['kind'] ?? 'notification',
            'title' => $this->data['title'] ?? $this->title(),
            'description' => $this->data['description'] ?? $this->data['message'] ?? '',
            'time' => $this->created_at?->diffForHumans(),
            'message' => $this->data['message'] ?? '',
            'data' => $this->data,
            'readAt' => $this->read_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }

    private function title(): string
    {
        return match ($this->data['kind'] ?? null) {
            'certificate_issued' => 'Sertifikat diterbitkan',
            'certificate_revoked' => 'Sertifikat dicabut',
            default => 'Notifikasi',
        };
    }
}
