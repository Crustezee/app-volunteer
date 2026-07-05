<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizerApplicationIndexRequest;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\VolunteerApplicationCollection;
use App\Http\Resources\VolunteerApplicationResource;
use App\Models\Organizer;
use App\Models\VolunteerApplication;
use App\Services\ApplicationStatusTransition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrganizerApplicationController extends Controller
{
    public function index(
        OrganizerApplicationIndexRequest $request,
        Organizer $organizer
    ): VolunteerApplicationCollection {
        $filters = $request->validated();
        $applications = $organizer->applications()
            ->with(['event.category', 'event.organizer', 'volunteerProfile', 'certificates.supersededBy'])
            ->when(
                $filters['eventId'] ?? null,
                fn (Builder $query, string $eventId) => $query->where('volunteer_applications.event_id', $eventId)
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status) => $query->where('volunteer_applications.status', $status)
            )
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $term = "%{$search}%";
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('volunteer_applications.role', 'like', $term)
                        ->orWhereHas('volunteerProfile', function (Builder $query) use ($term): void {
                            $query->where('name', 'like', $term)
                                ->orWhere('university', 'like', $term)
                                ->orWhere('major', 'like', $term);
                        })
                        ->orWhereHas('event', function (Builder $query) use ($term): void {
                            $query->where('title', 'like', $term)
                                ->orWhereHas('category', fn (Builder $query) => $query->where('name', 'like', $term));
                        });
                });
            });

        match ($filters['sort'] ?? 'latest') {
            'oldest' => $applications->orderBy('volunteer_applications.submitted_at')->orderBy('volunteer_applications.id'),
            'status' => $applications->orderBy('volunteer_applications.status')->orderByDesc('volunteer_applications.submitted_at')->orderBy('volunteer_applications.id'),
            default => $applications->orderByDesc('volunteer_applications.submitted_at')->orderBy('volunteer_applications.id'),
        };

        return new VolunteerApplicationCollection(
            $applications->paginate($filters['perPage'] ?? 20)->withQueryString()
        );
    }

    public function show(
        Organizer $organizer,
        VolunteerApplication $application
    ): VolunteerApplicationResource {
        $this->authorize('viewDashboard', $organizer);
        $application->load(['event.category', 'event.organizer', 'volunteerProfile', 'certificates.supersededBy']);

        return new VolunteerApplicationResource($application);
    }

    public function checkIn(
        Request $request,
        Organizer $organizer,
        VolunteerApplication $application
    ): VolunteerApplicationResource {
        $this->authorize('manage', $organizer);
        abort_unless(
            $application->event()->where('organizer_id', $organizer->id)->exists(),
            404,
            'Resource tidak ditemukan.'
        );

        if ($application->checked_in_at === null) {
            $application->update([
                'checked_in_at' => now(),
                'checked_in_by' => $request->user()?->id,
            ]);
        }

        $application->load(['event.category', 'event.organizer', 'volunteerProfile', 'certificates.supersededBy']);

        return new VolunteerApplicationResource($application);
    }

    public function updateStatus(
        UpdateApplicationStatusRequest $request,
        Organizer $organizer,
        VolunteerApplication $application,
        ApplicationStatusTransition $transition
    ): VolunteerApplicationResource {
        $target = ApplicationStatus::from($request->validated()['status']);
        $transition->validate($application->status, $target);

        $application->update(['status' => $target]);
        $application->load(['event.category', 'event.organizer', 'volunteerProfile', 'certificates.supersededBy']);

        return new VolunteerApplicationResource($application);
    }
}
