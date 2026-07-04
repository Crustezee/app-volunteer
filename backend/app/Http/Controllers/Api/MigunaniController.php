<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\OrganizerResource;
use App\Http\Resources\VolunteerProfileResource;
use App\Models\Category;
use App\Models\Organizer;
use App\Services\CurrentVolunteerProfile;
use App\Services\VolunteerProfileMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MigunaniController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json(['data' => ['ok' => true, 'service' => 'migunani-laravel-backend']]);
    }

    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::query()->get());
    }

    public function organizers(): AnonymousResourceCollection
    {
        return OrganizerResource::collection(Organizer::query()->get());
    }

    public function organizer(string $id): OrganizerResource
    {
        return new OrganizerResource(Organizer::query()->findOrFail($id));
    }

    public function profile(
        Request $request,
        CurrentVolunteerProfile $currentVolunteer,
        VolunteerProfileMetrics $metrics
    ): VolunteerProfileResource {
        $profile = $currentVolunteer->resolve($request->user());
        $this->authorize('view', $profile);

        return new VolunteerProfileResource($metrics->hydrate($profile));
    }
}
