<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizerDashboardResource;
use App\Models\Organizer;
use App\Services\OrganizerMetrics;

class OrganizerDashboardController extends Controller
{
    public function __invoke(
        Organizer $organizer,
        OrganizerMetrics $metrics
    ): OrganizerDashboardResource {
        $this->authorize('viewDashboard', $organizer);

        $events = $organizer->events()
            ->with(['category', 'organizer'])
            ->orderBy('date')
            ->get();
        $applications = $organizer->applications()
            ->with(['event.category', 'event.organizer', 'volunteerProfile'])
            ->orderByDesc('submitted_at')
            ->get();

        return new OrganizerDashboardResource([
            'organizer' => $organizer,
            'metrics' => $metrics->calculate($organizer, $events, $applications),
            'events' => $events,
            'applications' => $applications,
        ]);
    }
}
