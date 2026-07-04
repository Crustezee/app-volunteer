<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SavedEventIndexRequest;
use App\Http\Resources\VolunteerEventCollection;
use App\Http\Resources\VolunteerEventResource;
use App\Models\SavedEvent;
use App\Models\VolunteerEvent;
use App\Services\CurrentVolunteerProfile;
use App\Services\EventViewerContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SavedEventController extends Controller
{
    public function index(
        SavedEventIndexRequest $request,
        CurrentVolunteerProfile $currentVolunteer,
        EventViewerContext $viewerContext
    ): VolunteerEventCollection {
        $filters = $request->validated();
        $profile = $currentVolunteer->resolve($request->user());
        $events = $profile->savedVolunteerEvents()
            ->with(['category', 'organizer'])
            ->orderByPivot('created_at', 'desc')
            ->paginate($filters['perPage'] ?? 12)
            ->withQueryString();

        $viewerContext->apply($events->getCollection(), $profile);

        return new VolunteerEventCollection($events);
    }

    public function store(
        Request $request,
        VolunteerEvent $event,
        CurrentVolunteerProfile $currentVolunteer,
        EventViewerContext $viewerContext
    ): VolunteerEventResource {
        $this->authorize('create', SavedEvent::class);
        $profile = $currentVolunteer->resolve($request->user());

        SavedEvent::query()->firstOrCreate(
            ['event_id' => $event->id, 'volunteer_profile_id' => $profile->id],
            ['id' => 'sav-'.Str::lower(Str::random(12))]
        );

        $event->load(['category', 'organizer']);
        $viewerContext->apply(new Collection([$event]), $profile);

        return new VolunteerEventResource($event);
    }

    public function destroy(
        Request $request,
        VolunteerEvent $event,
        CurrentVolunteerProfile $currentVolunteer
    ): Response {
        $profile = $currentVolunteer->resolve($request->user());
        $savedEvent = $profile->savedEvents()
            ->where('event_id', $event->id)
            ->first();

        if ($savedEvent) {
            $this->authorize('delete', $savedEvent);
            $savedEvent->delete();
        }

        return response()->noContent();
    }
}
