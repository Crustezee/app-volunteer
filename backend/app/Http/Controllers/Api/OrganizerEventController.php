<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizerEventIndexRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\VolunteerEventCollection;
use App\Http\Resources\VolunteerEventResource;
use App\Models\Organizer;
use App\Models\VolunteerEvent;
use App\Services\EventStatusTransition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizerEventController extends Controller
{
    public function index(
        OrganizerEventIndexRequest $request,
        Organizer $organizer
    ): VolunteerEventCollection {
        $filters = $request->validated();
        $events = $organizer->events()
            ->with(['category', 'organizer'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $term = "%{$search}%";
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('location', 'like', $term)
                        ->orWhereHas('category', fn (Builder $query) => $query->where('name', 'like', $term));
                });
            })
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status) => $query->where('status', $status)
            )
            ->orderBy('date')
            ->orderBy('id')
            ->paginate($filters['perPage'] ?? 12)
            ->withQueryString();

        return new VolunteerEventCollection($events);
    }

    public function store(
        StoreEventRequest $request,
        Organizer $organizer
    ): JsonResponse {
        $data = $request->validated();

        $event = DB::transaction(function () use ($data, $organizer): VolunteerEvent {
            $event = $organizer->events()->create([
                'id' => 'evt-'.Str::lower(Str::random(12)),
                'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
                'title' => $data['title'],
                'category_id' => $data['categoryId'],
                'location' => $data['location'],
                'city' => $data['city'],
                'mode' => $data['mode'],
                'date' => $data['date'],
                'start_time' => $data['startTime'],
                'end_time' => $data['endTime'],
                'duration_hours' => $this->duration($data['startTime'], $data['endTime']),
                'quota' => $data['quota'],
                'registered' => 0,
                'status' => EventStatus::Open,
                'image' => $data['image'] ?? 'https://images.unsplash.com/photo-1559027615-cd4628902d4a?auto=format&fit=crop&w=1200&q=80',
                'short_description' => $data['shortDescription'] ?? $data['description'],
                'description' => $data['description'],
                'benefits' => $data['benefits'],
                'skills' => $data['skills'],
                'roles' => $data['roles'],
                'impact_target' => ($data['quota'] * 3).' penerima manfaat.',
                'tags' => $data['tags'] ?? [$data['mode'], $data['categoryId']],
                'featured' => false,
            ]);

            $organizer->update(['total_events' => $organizer->total_events + 1]);

            return $event;
        });

        $event->load(['category', 'organizer']);

        return (new VolunteerEventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        Organizer $organizer,
        VolunteerEvent $event
    ): VolunteerEventResource {
        $this->authorize('viewDashboard', $organizer);
        $event->load(['category', 'organizer']);

        return new VolunteerEventResource($event);
    }

    public function update(
        UpdateEventRequest $request,
        Organizer $organizer,
        VolunteerEvent $event,
        EventStatusTransition $transition
    ): VolunteerEventResource {
        $data = $request->validated();

        if (array_key_exists('status', $data)) {
            $transition->validate($event->status, EventStatus::from($data['status']));
        }

        $mapping = [
            'title' => 'title',
            'categoryId' => 'category_id',
            'location' => 'location',
            'city' => 'city',
            'mode' => 'mode',
            'date' => 'date',
            'startTime' => 'start_time',
            'endTime' => 'end_time',
            'quota' => 'quota',
            'status' => 'status',
            'description' => 'description',
            'shortDescription' => 'short_description',
            'image' => 'image',
            'benefits' => 'benefits',
            'skills' => 'skills',
            'roles' => 'roles',
            'tags' => 'tags',
        ];
        $updates = [];

        foreach ($mapping as $input => $column) {
            if (array_key_exists($input, $data)) {
                $updates[$column] = $data[$input];
            }
        }

        if (array_key_exists('startTime', $data) || array_key_exists('endTime', $data)) {
            $updates['duration_hours'] = $this->duration(
                $data['startTime'] ?? $event->start_time,
                $data['endTime'] ?? $event->end_time
            );
        }

        if (array_key_exists('quota', $data)) {
            $updates['impact_target'] = ($data['quota'] * 3).' penerima manfaat.';
        }

        if (
            in_array($event->status, [EventStatus::Open, EventStatus::NearlyFull], true)
            && array_key_exists('quota', $data)
            && ! array_key_exists('status', $data)
        ) {
            $updates['status'] = $this->availableStatus($event->registered, $data['quota']);
        }

        if (($data['status'] ?? null) === EventStatus::Open->value) {
            $updates['status'] = $this->availableStatus(
                $event->registered,
                $data['quota'] ?? $event->quota
            );
        }

        $event->update($updates);
        $event->load(['category', 'organizer']);

        return new VolunteerEventResource($event);
    }

    private function duration(string $startTime, string $endTime): int
    {
        $start = Carbon::createFromFormat('H:i', substr($startTime, 0, 5));
        $end = Carbon::createFromFormat('H:i', substr($endTime, 0, 5));

        return (int) ceil($start->diffInMinutes($end) / 60);
    }

    private function availableStatus(int $registered, int $quota): EventStatus
    {
        if ($registered >= $quota) {
            return EventStatus::Closed;
        }

        return $registered / $quota >= 0.9
            ? EventStatus::NearlyFull
            : EventStatus::Open;
    }
}
