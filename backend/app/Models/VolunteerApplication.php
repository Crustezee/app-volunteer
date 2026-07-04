<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class VolunteerApplication extends Model
{
    use HasAuditColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'event_id', 'volunteer_profile_id', 'role', 'status', 'submitted_at', 'motivation', 'availability'];

    protected $casts = ['availability' => 'array', 'status' => ApplicationStatus::class];

    protected static function booted(): void
    {
        static::updating(function (VolunteerApplication $application): void {
            if (
                $application->isDirty('status')
                && $application->status !== ApplicationStatus::Completed
                && $application->certificates()->exists()
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Application bersertifikat harus tetap berstatus selesai.',
                ]);
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(VolunteerEvent::class, 'event_id');
    }

    public function volunteerProfile(): BelongsTo
    {
        return $this->belongsTo(VolunteerProfile::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class, 'application_id')
            ->where('status', AppnumsCertificateStatus::Issued->value);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'application_id');
    }
}
