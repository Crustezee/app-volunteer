<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\CertificateStatus;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class Certificate extends Model
{
    use HasAuditColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'application_id',
        'issued_at',
        'credential_id',
        'hours',
        'status',
        'revision_number',
        'supersedes_certificate_id',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'volunteer_name_snapshot',
        'event_title_snapshot',
        'organizer_name_snapshot',
        'role_snapshot',
        'event_date_snapshot',
    ];

    protected $casts = [
        'hours' => 'integer',
        'revision_number' => 'integer',
        'status' => CertificateStatus::class,
        'issued_at' => 'date:Y-m-d',
        'event_date_snapshot' => 'date:Y-m-d',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Certificate $certificate): void {
            $application = $certificate->application()
                ->with(['event.organizer', 'volunteerProfile'])
                ->firstOrFail();

            if ($application->status !== ApplicationStatus::Completed) {
                throw ValidationException::withMessages([
                    'applicationId' => 'Sertifikat hanya dapat diterbitkan untuk application yang selesai.',
                ]);
            }

            $certificate->status ??= CertificateStatus::Issued;
            $certificate->revision_number ??= ((int) self::query()
                ->where('application_id', $application->id)
                ->max('revision_number')) + 1;
            $certificate->volunteer_name_snapshot ??= $application->volunteerProfile->name;
            $certificate->event_title_snapshot ??= $application->event->title;
            $certificate->organizer_name_snapshot ??= $application->event->organizer->name;
            $certificate->role_snapshot ??= $application->role;
            $certificate->event_date_snapshot ??= $application->event->date;

            if (
                $certificate->status === CertificateStatus::Issued
                && self::query()
                    ->where('application_id', $application->id)
                    ->where('status', CertificateStatus::Issued->value)
                    ->whereKeyNot($certificate->getKey())
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'applicationId' => 'Application sudah memiliki sertifikat aktif.',
                ]);
            }
        });
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', CertificateStatus::Issued->value);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(VolunteerApplication::class, 'application_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_certificate_id');
    }

    public function supersededBy(): HasOne
    {
        return $this->hasOne(self::class, 'supersedes_certificate_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
