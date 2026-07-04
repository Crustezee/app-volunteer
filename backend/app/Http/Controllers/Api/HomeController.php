<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\HomeResource;
use App\Models\Category;
use App\Models\Organizer;
use App\Models\VolunteerEvent;
use App\Services\EventViewerContext;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        EventViewerContext $viewerContext
    ): HomeResource {
        $availableStatuses = [
            EventStatus::Open->value,
            EventStatus::NearlyFull->value,
        ];
        $featuredEvents = VolunteerEvent::query()
            ->with(['category', 'organizer'])
            ->where('featured', true)
            ->whereIn('status', $availableStatuses)
            ->orderBy('date')
            ->limit(3)
            ->get();

        $viewerContext->apply($featuredEvents, $request->user()?->volunteerProfile);

        return new HomeResource([
            'stats' => [
                'eventCount' => VolunteerEvent::query()->count(),
                'availableEvents' => VolunteerEvent::query()->whereIn('status', $availableStatuses)->count(),
                'totalSlots' => (int) VolunteerEvent::query()->sum('quota'),
                'totalRegistered' => (int) VolunteerEvent::query()->sum('registered'),
                'categoryCount' => Category::query()->count(),
                'organizerCount' => Organizer::query()->count(),
            ],
            'categories' => Category::query()->orderBy('name')->limit(4)->get(),
            'featuredEvents' => $featuredEvents,
        ]);
    }
}
