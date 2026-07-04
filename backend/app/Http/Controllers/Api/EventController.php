<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventIndexRequest;
use App\Http\Resources\VolunteerEventCollection;
use App\Http\Resources\VolunteerEventResource;
use App\Models\VolunteerEvent;
use App\Services\EventViewerContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(
        EventIndexRequest $request,
        EventViewerContext $viewerContext
    ): VolunteerEventCollection {
        $filters = $request->validated();
        $search = $filters['q'] ?? null;
        $query = VolunteerEvent::query()
            ->with(['category', 'organizer'])
            ->when($search, fn (Builder $query) => $this->applySearch($query, $search))
            ->when($filters['categoryId'] ?? null, fn (Builder $query, string $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['mode'] ?? null, fn (Builder $query, string $mode) => $query->where('mode', $mode))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(
                array_key_exists('featured', $filters),
                fn (Builder $query) => $query->where('featured', $filters['featured'])
            );

        $this->applySort($query, $filters['sort'] ?? 'relevance', $search);

        $events = $query
            ->paginate($filters['perPage'] ?? 12)
            ->withQueryString();

        $viewerContext->apply(
            $events->getCollection(),
            $request->user()?->volunteerProfile
        );

        return new VolunteerEventCollection($events);
    }

    public function show(
        Request $request,
        string $idOrSlug,
        EventViewerContext $viewerContext
    ): VolunteerEventResource {
        $event = VolunteerEvent::query()
            ->with(['category', 'organizer'])
            ->where(fn (Builder $query) => $query
                ->where('id', $idOrSlug)
                ->orWhere('slug', $idOrSlug))
            ->firstOrFail();

        $relatedEvents = VolunteerEvent::query()
            ->with(['category', 'organizer'])
            ->where('id', '!=', $event->id)
            ->where('category_id', $event->category_id)
            ->orderBy('date')
            ->limit(3)
            ->get();
        $profile = $request->user()?->volunteerProfile;

        $viewerContext->applyDetail($event, $profile);
        $viewerContext->apply($relatedEvents, $profile);
        $event->setRelation('relatedEvents', $relatedEvents);

        return new VolunteerEventResource($event);
    }

    private function applySearch(Builder $query, string $search): void
    {
        $term = "%{$search}%";

        $query->where(function (Builder $query) use ($term): void {
            $query->where('title', 'like', $term)
                ->orWhere('short_description', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhere('city', 'like', $term)
                ->orWhere('location', 'like', $term)
                ->orWhere('mode', 'like', $term)
                ->orWhere('tags', 'like', $term)
                ->orWhereHas('category', fn (Builder $query) => $query->where('name', 'like', $term))
                ->orWhereHas('organizer', fn (Builder $query) => $query->where('name', 'like', $term));
        });
    }

    private function applySort(Builder $query, string $sort, ?string $search): void
    {
        match ($sort) {
            'latest' => $query->orderByDesc('created_at')->orderBy('id'),
            'eventDate' => $query->orderBy('date')->orderBy('start_time')->orderBy('id'),
            'remainingQuota' => $query->orderByRaw('(quota - registered) DESC')->orderBy('date')->orderBy('id'),
            default => $this->applyRelevanceSort($query, $search),
        };
    }

    private function applyRelevanceSort(Builder $query, ?string $search): void
    {
        if ($search) {
            $normalized = mb_strtolower($search);
            $query->orderByRaw(
                'CASE WHEN LOWER(title) = ? THEN 0 WHEN LOWER(title) LIKE ? THEN 1 ELSE 2 END',
                [$normalized, "%{$normalized}%"]
            );
        }

        $query->orderByDesc('featured')->orderBy('date')->orderBy('id');
    }
}
