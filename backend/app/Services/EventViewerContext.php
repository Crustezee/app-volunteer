<?php

namespace App\Services;

use App\Models\VolunteerEvent;
use App\Models\VolunteerProfile;
use Illuminate\Database\Eloquent\Collection;

class EventViewerContext
{
    /**
     * @param  Collection<int, VolunteerEvent>  $events
     */
    public function apply(Collection $events, ?VolunteerProfile $profile): void
    {
        $savedEventIds = $profile
            ? $profile->savedEvents()->whereIn('event_id', $events->modelKeys())->pluck('event_id')
            : collect();

        $events->each(
            fn (VolunteerEvent $event) => $event->setAttribute(
                'is_saved',
                $savedEventIds->contains($event->id)
            )
        );
    }

    public function applyDetail(VolunteerEvent $event, ?VolunteerProfile $profile): void
    {
        $this->apply(new Collection([$event]), $profile);

        $application = $profile
            ? $profile->applications()->where('event_id', $event->id)->first()
            : null;

        $event->setRelation('myApplication', $application);
    }
}
