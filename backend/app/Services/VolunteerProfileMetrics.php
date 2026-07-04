<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Certificate;
use App\Models\VolunteerProfile;
use Illuminate\Database\Eloquent\Builder;

class VolunteerProfileMetrics
{
    public function hydrate(VolunteerProfile $profile): VolunteerProfile
    {
        $certificates = $this->certificateQuery($profile);

        $profile->setAttribute('total_hours', (int) (clone $certificates)->sum('hours'));
        $profile->setAttribute(
            'completed_events',
            $profile->applications()
                ->where('status', ApplicationStatus::Completed->value)
                ->count()
        );
        $profile->setAttribute('certificates_count', (clone $certificates)->count());
        $profile->load('savedEvents');

        return $profile;
    }

    /**
     * @return list<array{id: string, label: string, value: string, delta: string}>
     */
    public function stats(VolunteerProfile $profile): array
    {
        return [
            ['id' => 'hours', 'label' => 'Jam kontribusi', 'value' => (string) $profile->total_hours, 'delta' => 'Dihitung dari sertifikat'],
            ['id' => 'events', 'label' => 'Event selesai', 'value' => (string) $profile->completed_events, 'delta' => 'Application berstatus selesai'],
            ['id' => 'certificates', 'label' => 'Sertifikat', 'value' => (string) $profile->certificates_count, 'delta' => 'Sertifikat telah diterbitkan'],
            ['id' => 'saved', 'label' => 'Event tersimpan', 'value' => (string) $profile->savedEvents->count(), 'delta' => 'Peluang yang disimpan'],
        ];
    }

    private function certificateQuery(VolunteerProfile $profile): Builder
    {
        return Certificate::query()->issued()->whereHas(
            'application',
            fn (Builder $query) => $query->where('volunteer_profile_id', $profile->id)
        );
    }
}
