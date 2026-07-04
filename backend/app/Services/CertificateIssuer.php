<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Notifications\CertificateIssuedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CertificateIssuer
{
    /** @param array{hours: int, issuedAt?: string|null, supersedesCertificateId?: string|null} $data */
    public function issue(VolunteerApplication $application, array $data, User $actor): Certificate
    {
        $certificate = DB::transaction(function () use ($application, $data): Certificate {
            $application = VolunteerApplication::query()
                ->with(['event.organizer', 'volunteerProfile.user'])
                ->lockForUpdate()
                ->findOrFail($application->id);

            if ($application->certificates()->issued()->exists()) {
                throw ValidationException::withMessages([
                    'applicationId' => 'Application sudah memiliki sertifikat aktif.',
                ]);
            }

            $latest = $application->certificates()
                ->orderByDesc('revision_number')
                ->lockForUpdate()
                ->first();
            $supersedesId = $data['supersedesCertificateId'] ?? null;

            if ($latest && ! $supersedesId) {
                throw ValidationException::withMessages([
                    'supersedesCertificateId' => 'Revisi harus merujuk sertifikat terakhir yang telah dicabut.',
                ]);
            }

            if ($supersedesId) {
                $supersedes = $application->certificates()->find($supersedesId);

                if (! $supersedes || $supersedes->status !== CertificateStatus::Revoked) {
                    throw ValidationException::withMessages([
                        'supersedesCertificateId' => 'Sertifikat pengganti harus merujuk revisi revoked pada application yang sama.',
                    ]);
                }

                if ($latest?->id !== $supersedes->id || $supersedes->supersededBy()->exists()) {
                    throw ValidationException::withMessages([
                        'supersedesCertificateId' => 'Sertifikat yang dipilih bukan revisi terakhir atau sudah memiliki pengganti.',
                    ]);
                }
            }

            return Certificate::query()->create([
                'id' => 'crt-'.Str::lower(Str::random(12)),
                'application_id' => $application->id,
                'issued_at' => $data['issuedAt'] ?? now()->toDateString(),
                'credential_id' => $this->credentialId(),
                'hours' => $data['hours'],
                'status' => CertificateStatus::Issued,
                'revision_number' => ($latest?->revision_number ?? 0) + 1,
                'supersedes_certificate_id' => $supersedesId,
            ]);
        });

        $certificate->load(['application.event.organizer', 'application.volunteerProfile.user', 'supersedes']);
        $certificate->application->volunteerProfile->user
            ->notify(new CertificateIssuedNotification($certificate));

        return $certificate;
    }

    private function credentialId(): string
    {
        do {
            $credentialId = 'MGN-'.now()->format('Y').'-'.Str::upper(Str::random(10));
        } while (Certificate::query()->where('credential_id', $credentialId)->exists());

        return $credentialId;
    }
}
