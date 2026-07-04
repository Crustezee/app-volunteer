<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Models\Organizer;
use App\Models\VolunteerEvent;
use Illuminate\Support\Collection;

class OrganizerMetrics
{
    /**
     * @param  Collection<int, VolunteerEvent>  $events
     * @param  Collection<int, mixed>  $applications
     * @return list<array{id: string, label: string, value: string, helper: string}>
     */
    public function calculate(
        Organizer $organizer,
        Collection $events,
        Collection $applications
    ): array {
        $activeEvents = $events
            ->whereIn('status', [EventStatus::Open, EventStatus::NearlyFull])
            ->count();
        $fillRate = $events->isEmpty()
            ? 0
            : (int) round($events->average(
                fn (VolunteerEvent $event) => $event->quota > 0
                    ? ($event->registered / $event->quota) * 100
                    : 0
            ));

        return [
            ['id' => 'active-events', 'label' => 'Event aktif', 'value' => (string) $activeEvents, 'helper' => 'Sedang menerima relawan'],
            ['id' => 'applicants', 'label' => 'Total pendaftar', 'value' => (string) $applications->count(), 'helper' => 'Dihitung dari application'],
            ['id' => 'fill-rate', 'label' => 'Rata-rata keterisian', 'value' => $fillRate.'%', 'helper' => 'Berdasarkan kuota event'],
            ['id' => 'response', 'label' => 'Response time', 'value' => $organizer->response_time, 'helper' => 'Target respons organizer'],
        ];
    }
}
