<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CertificateRevokedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Certificate $certificate) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'certificate_revoked',
            'certificateId' => $this->certificate->id,
            'credentialId' => $this->certificate->credential_id,
            'eventTitle' => $this->certificate->event_title_snapshot,
            'reason' => $this->certificate->revocation_reason,
            'message' => "Sertifikat {$this->certificate->event_title_snapshot} telah dicabut.",
        ];
    }
}
