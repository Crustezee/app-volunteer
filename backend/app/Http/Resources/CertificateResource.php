<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'applicationId' => $this->application_id,
            'eventId' => $this->when(
                $this->relationLoaded('application'),
                fn () => $this->application->event_id
            ),
            'issuedAt' => $this->issued_at?->toDateString(),
            'credentialId' => $this->credential_id,
            'hours' => $this->hours,
            'status' => $this->status->value,
            'revisionNumber' => $this->revision_number,
            'supersedesCertificateId' => $this->supersedes_certificate_id,
            'replacementCertificateId' => $this->whenLoaded(
                'supersededBy',
                fn () => $this->supersededBy?->id
            ),
            'replacementCredentialId' => $this->whenLoaded(
                'supersededBy',
                fn () => $this->supersededBy?->credential_id
            ),
            'revokedAt' => $this->revoked_at?->toIso8601String(),
            'revocationReason' => $this->revocation_reason,
            'snapshot' => [
                'volunteerName' => $this->volunteer_name_snapshot,
                'eventTitle' => $this->event_title_snapshot,
                'organizerName' => $this->organizer_name_snapshot,
                'role' => $this->role_snapshot,
                'eventDate' => $this->event_date_snapshot?->toDateString(),
            ],
            'event' => $this->when(
                $this->relationLoaded('application') && $this->application->relationLoaded('event'),
                fn () => new VolunteerEventResource($this->application->event)
            ),
            'volunteerProfile' => $this->when(
                $this->relationLoaded('application') && $this->application->relationLoaded('volunteerProfile'),
                fn () => new VolunteerProfileResource($this->application->volunteerProfile)
            ),
        ];
    }
}
