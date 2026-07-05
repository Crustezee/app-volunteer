<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\EventStatus;
use App\Exceptions\ConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\VolunteerApplicationIndexRequest;
use App\Http\Resources\VolunteerApplicationCollection;
use App\Http\Resources\VolunteerApplicationResource;
use App\Models\VolunteerApplication;
use App\Models\VolunteerEvent;
use App\Services\CurrentVolunteerProfile;
use App\Services\EventViewerContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VolunteerApplicationController extends Controller
{
    public function index(
        VolunteerApplicationIndexRequest $request,
        CurrentVolunteerProfile $currentVolunteer,
        EventViewerContext $viewerContext
    ): VolunteerApplicationCollection {
        $filters = $request->validated();
        $profile = $currentVolunteer->resolve($request->user());
        $applications = $profile->applications()
            ->with(['event.category', 'event.organizer', 'volunteerProfile'])
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('status', $status)
            )
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->paginate($filters['perPage'] ?? 10)
            ->withQueryString();

        $viewerContext->apply(
            new Collection($applications->getCollection()->pluck('event')->all()),
            $profile
        );

        return new VolunteerApplicationCollection($applications);
    }

    public function store(
        StoreApplicationRequest $request,
        VolunteerEvent $event,
        CurrentVolunteerProfile $currentVolunteer,
        EventViewerContext $viewerContext
    ): JsonResponse {
        $this->authorize('create', VolunteerApplication::class);
        $data = $request->validated();
        $profile = $currentVolunteer->resolve($request->user());

        $application = DB::transaction(function () use ($data, $event, $profile): VolunteerApplication {
            $event = VolunteerEvent::query()->lockForUpdate()->findOrFail($event->id);

            if ($profile->applications()->where('event_id', $event->id)->exists()) {
                throw new ConflictException('Volunteer sudah mendaftar pada event ini.');
            }

            if (
                $event->registered >= $event->quota
                || in_array(
                    $event->status,
                    [EventStatus::Closed, EventStatus::Cancelled, EventStatus::Completed],
                    true
                )
            ) {
                throw new ConflictException('Event tidak lagi menerima pendaftaran.');
            }

            $application = $profile->applications()->create([
                'id' => 'app-'.Str::lower(Str::random(12)),
                'event_id' => $event->id,
                'role' => $data['role'],
                'status' => ApplicationStatus::Submitted,
                'submitted_at' => now()->toDateString(),
                'motivation' => $data['motivation'],
                'availability' => $data['availability'],
            ]);

            $event->increment('registered');
            $event->refresh();
            $event->update([
                'status' => match (true) {
                    $event->registered >= $event->quota => EventStatus::Closed,
                    $event->registered / $event->quota >= 0.9 => EventStatus::NearlyFull,
                    default => EventStatus::Open,
                },
            ]);

            return $application;
        });

        $application->load(['event.category', 'event.organizer', 'volunteerProfile']);
        $viewerContext->apply(new Collection([$application->event]), $profile);

        return (new VolunteerApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(
        Request $request,
        string $application,
        CurrentVolunteerProfile $currentVolunteer,
        EventViewerContext $viewerContext
    ): VolunteerApplicationResource {
        $profile = $currentVolunteer->resolve($request->user());

        $application = DB::transaction(function () use ($application, $profile): VolunteerApplication {
            $application = $profile->applications()
                ->with('event')
                ->lockForUpdate()
                ->findOrFail($application);

            if (! in_array($application->status, [
                ApplicationStatus::Submitted,
                ApplicationStatus::Waitlisted,
                ApplicationStatus::Accepted,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Application hanya bisa dibatalkan dari status Submitted, Waitlisted, atau Accepted.'],
                ]);
            }

            $application->update(['status' => ApplicationStatus::Cancelled]);

            $event = VolunteerEvent::query()
                ->lockForUpdate()
                ->find($application->event_id);

            if ($event && $event->registered > 0) {
                $event->decrement('registered');
                $event->refresh();

                if ($event->status === EventStatus::Closed && $event->registered < $event->quota) {
                    $event->update([
                        'status' => $event->registered / $event->quota >= 0.9
                            ? EventStatus::NearlyFull
                            : EventStatus::Open,
                    ]);
                }
            }

            return $application;
        });

        $application->load(['event.category', 'event.organizer', 'volunteerProfile']);
        $viewerContext->apply(new Collection([$application->event]), $profile);

        return new VolunteerApplicationResource($application);
    }
}
