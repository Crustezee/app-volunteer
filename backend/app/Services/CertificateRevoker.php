<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\User;
use App\Notifications\CertificateRevokedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CertificateRevoker
{
    public function revoke(Certificate $certificate, string $reason, User $actor): Certificate
    {
        $certificate = DB::transaction(function () use ($certificate, $reason, $actor): Certificate {
            $certificate = Certificate::query()->lockForUpdate()->findOrFail($certificate->id);

            if ($certificate->status !== CertificateStatus::Issued) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya sertifikat aktif yang dapat dicabut.',
                ]);
            }

            $certificate->update([
                'status' => CertificateStatus::Revoked,
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
                'revocation_reason' => $reason,
            ]);

            return $certificate;
        });

        $certificate->load(['application.volunteerProfile.user', 'supersededBy']);
        $certificate->application->volunteerProfile->user
            ->notify(new CertificateRevokedNotification($certificate));

        return $certificate;
    }
}
