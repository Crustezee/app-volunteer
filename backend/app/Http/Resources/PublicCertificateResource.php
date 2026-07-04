<?php

namespace App\Http\Resources;

use App\Enums\CertificateStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCertificateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'credentialId' => $this->credential_id,
            'status' => $this->status->value,
            'isValid' => $this->status === CertificateStatus::Issued,
            'revisionNumber' => $this->revision_number,
            'volunteerName' => $this->volunteer_name_snapshot,
            'eventTitle' => $this->event_title_snapshot,
            'organizerName' => $this->organizer_name_snapshot,
            'role' => $this->role_snapshot,
            'eventDate' => $this->event_date_snapshot?->toDateString(),
            'issuedAt' => $this->issued_at?->toDateString(),
            'hours' => $this->hours,
            'revokedAt' => $this->revoked_at?->toIso8601String(),
            'revocationReason' => $this->when(
                $this->status === CertificateStatus::Revoked,
                $this->revocation_reason
            ),
            'replacementCredentialId' => $this->whenLoaded(
                'supersededBy',
                fn () => $this->supersededBy?->credential_id
            ),
        ];
    }
}
