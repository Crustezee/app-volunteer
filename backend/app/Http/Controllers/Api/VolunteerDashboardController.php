<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VolunteerDashboardResource;
use App\Models\Certificate;
use App\Services\CurrentVolunteerProfile;
use App\Services\EventViewerContext;
use App\Services\VolunteerProfileMetrics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class VolunteerDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentVolunteerProfile $currentVolunteer,
        VolunteerProfileMetrics $metrics,
        EventViewerContext $viewerContext
    ): VolunteerDashboardResource {
        $profile = $metrics->hydrate($currentVolunteer->resolve($request->user()));
        $applications = $profile->applications()
            ->with(['event.category', 'event.organizer', 'volunteerProfile'])
            ->orderByDesc('submitted_at')
            ->get();
        $certificates = Certificate::query()
            ->with(['application.event.category', 'application.event.organizer', 'application.volunteerProfile', 'supersededBy'])
            ->whereHas(
                'application',
                fn (Builder $query) => $query->where('volunteer_profile_id', $profile->id)
            )
            ->orderByDesc('issued_at')
            ->get();
        $savedEvents = $profile->savedVolunteerEvents()
            ->with(['category', 'organizer'])
            ->orderByPivot('created_at', 'desc')
            ->get();

        $events = new Collection([
            ...$applications->pluck('event')->all(),
            ...$certificates->pluck('application.event')->all(),
            ...$savedEvents->all(),
        ]);
        $viewerContext->apply($events, $profile);

        return new VolunteerDashboardResource([
            'profile' => $profile,
            'stats' => $metrics->stats($profile),
            'applications' => $applications,
            'certificates' => $certificates,
            'savedEvents' => $savedEvents,
            'notifications' => $request->user()->notifications()->latest()->limit(10)->get(),
        ]);
    }
}
