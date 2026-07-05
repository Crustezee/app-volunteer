<?php

namespace App\Http\Controllers\Api;

use App\Enums\CertificateStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAdminEventStatusRequest;
use App\Http\Requests\UpdateOrganizerVerificationRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Http\Resources\OrganizerResource;
use App\Http\Resources\PlatformUserResource;
use App\Http\Resources\VolunteerEventResource;
use App\Models\Certificate;
use App\Models\Organizer;
use App\Models\User;
use App\Models\VolunteerEvent;
use App\Models\VolunteerProfile;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminController extends Controller
{
    public function dashboard(Request $request): array
    {
        $this->authorizeAdmin($request);

        $users = User::query()->with(['volunteerProfile', 'organizers'])->latest()->get();
        $events = VolunteerEvent::query()->with(['category', 'organizer'])->latest()->get();
        $organizers = Organizer::query()->orderBy('name')->get();

        return [
            'data' => [
                'stats' => $this->stats(),
                'users' => PlatformUserResource::collection($users),
                'events' => VolunteerEventResource::collection($events),
                'organizers' => OrganizerResource::collection($organizers),
            ],
        ];
    }

    public function users(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAdmin($request);

        return PlatformUserResource::collection(
            User::query()->with(['volunteerProfile', 'organizers'])->latest()->get()
        );
    }

    public function events(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAdmin($request);

        return VolunteerEventResource::collection(
            VolunteerEvent::query()->with(['category', 'organizer'])->latest()->get()
        );
    }

    public function organizers(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAdmin($request);

        return OrganizerResource::collection(
            Organizer::query()->orderBy('name')->get()
        );
    }

    public function updateUserStatus(UpdateUserStatusRequest $request, string $user): PlatformUserResource
    {
        $user = $this->resolvePlatformUser($user);
        $user->update($request->validated());
        $user->load(['volunteerProfile', 'organizers']);

        return new PlatformUserResource($user);
    }

    public function updateOrganizerVerification(
        UpdateOrganizerVerificationRequest $request,
        Organizer $organizer
    ): OrganizerResource {
        $organizer->update($request->validated());

        return new OrganizerResource($organizer);
    }

    public function updateEventStatus(
        UpdateAdminEventStatusRequest $request,
        VolunteerEvent $event
    ): VolunteerEventResource {
        $event->update($request->validated());
        $event->load(['category', 'organizer']);

        return new VolunteerEventResource($event);
    }

    /**
     * @return list<array{id: string, label: string, value: string, helper: string}>
     */
    private function stats(): array
    {
        $totalUsers = User::query()->count();
        $totalEvents = VolunteerEvent::query()->count();
        $activeEvents = VolunteerEvent::query()
            ->whereIn('status', [EventStatus::Open->value, EventStatus::NearlyFull->value])
            ->count();
        $totalOrganizers = Organizer::query()->count();
        $verifiedOrganizers = Organizer::query()->where('verified', true)->count();
        $totalHours = Certificate::query()->where('status', CertificateStatus::Issued->value)->sum('hours');

        return [
            [
                'id' => 'total-users',
                'label' => 'Total pengguna',
                'value' => number_format($totalUsers, 0, ',', '.'),
                'helper' => '+'.User::query()->where('created_at', '>=', now()->subMonth())->count().' bulan ini',
            ],
            [
                'id' => 'total-events',
                'label' => 'Total event',
                'value' => number_format($totalEvents, 0, ',', '.'),
                'helper' => $activeEvents.' event aktif',
            ],
            [
                'id' => 'total-organizers',
                'label' => 'Organizer terdaftar',
                'value' => number_format($totalOrganizers, 0, ',', '.'),
                'helper' => $verifiedOrganizers.' terverifikasi',
            ],
            [
                'id' => 'total-hours',
                'label' => 'Total jam kontribusi',
                'value' => number_format((int) $totalHours, 0, ',', '.'),
                'helper' => 'dari seluruh relawan',
            ],
        ];
    }

    private function authorizeAdmin(Request $request): void
    {
        if ($request->user()?->role !== 'admin') {
            throw new AuthorizationException;
        }
    }

    private function resolvePlatformUser(string $identifier): User
    {
        if (ctype_digit($identifier)) {
            return User::query()->findOrFail((int) $identifier);
        }

        if (str_starts_with($identifier, 'usr-')) {
            $numericId = substr($identifier, 4);

            if (ctype_digit($numericId)) {
                return User::query()->findOrFail((int) $numericId);
            }
        }

        $profile = VolunteerProfile::query()
            ->with('user')
            ->findOrFail($identifier);

        return $profile->user;
    }
}
