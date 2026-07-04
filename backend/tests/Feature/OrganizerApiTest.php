<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\OrganizerMemberRole;
use App\Models\Organizer;
use App\Models\OrganizerMember;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerProfile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    public function test_member_can_read_scoped_dashboard_and_outsider_cannot(): void
    {
        $organizer = $this->organizer();
        $member = $this->createOrganizerUser($organizer, OrganizerMemberRole::Member);
        $outsider = User::factory()->create();

        $this->actingAs($member)
            ->getJson("/api/organizers/{$organizer->id}/dashboard")
            ->assertOk()
            ->assertJsonPath('data.organizer.id', $organizer->id)
            ->assertJsonCount(3, 'data.events')
            ->assertJsonCount(3, 'data.applications')
            ->assertJsonPath('data.metrics.1.value', '3');

        $this->actingAs($outsider)
            ->getJson("/api/organizers/{$organizer->id}/dashboard")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Akses ditolak.']);
    }

    public function test_event_list_supports_filters_pagination_and_scoped_binding(): void
    {
        $organizer = $this->organizer();
        $member = $this->createOrganizerUser($organizer, OrganizerMemberRole::Member);

        $this->actingAs($member)
            ->getJson("/api/organizers/{$organizer->id}/events?q=Mentoring&status=Open&perPage=1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'evt-008')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.perPage', 1);

        $this->getJson("/api/organizers/{$organizer->id}/events/evt-002")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource tidak ditemukan.']);
    }

    public function test_owner_can_create_and_update_event_with_route_scope_and_audit_fields(): void
    {
        $organizer = $this->organizer();
        $owner = $this->owner();
        $totalEvents = $organizer->total_events;

        $response = $this->actingAs($owner)
            ->postJson("/api/organizers/{$organizer->id}/events", $this->eventPayload())
            ->assertCreated()
            ->assertJsonPath('data.organizerId', $organizer->id)
            ->assertJsonPath('data.durationHours', 4)
            ->assertJsonPath('data.status', 'Open');

        $eventId = $response->json('data.id');
        $slug = $response->json('data.slug');

        $this->assertDatabaseHas('volunteer_events', [
            'id' => $eventId,
            'organizer_id' => $organizer->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $this->assertDatabaseHas('organizers', [
            'id' => $organizer->id,
            'total_events' => $totalEvents + 1,
            'updated_by' => $owner->id,
        ]);

        $this->patchJson("/api/organizers/{$organizer->id}/events/{$eventId}", [
            'title' => 'Event Organizer Diperbarui',
            'startTime' => '09:00',
            'endTime' => '11:30',
            'quota' => 31,
            'status' => 'Closed',
        ])->assertOk()
            ->assertJsonPath('data.slug', $slug)
            ->assertJsonPath('data.durationHours', 3)
            ->assertJsonPath('data.impactTarget', '93 penerima manfaat.')
            ->assertJsonPath('data.status', 'Closed');
    }

    public function test_event_mutations_enforce_role_scope_and_business_validation(): void
    {
        $organizer = $this->organizer();
        $member = $this->createOrganizerUser($organizer, OrganizerMemberRole::Member);

        $this->actingAs($member)
            ->postJson("/api/organizers/{$organizer->id}/events", $this->eventPayload())
            ->assertForbidden();

        $this->patchJson("/api/organizers/{$organizer->id}/events/evt-001", [
            'title' => 'Tidak Boleh Diubah',
        ])->assertForbidden();

        $this->actingAs($this->owner())
            ->patchJson("/api/organizers/{$organizer->id}/events/evt-001", [
                'quota' => 26,
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('quota');

        $this->patchJson("/api/organizers/{$organizer->id}/events/evt-001", [
            'startTime' => '13:00',
            'endTime' => '12:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('endTime');

        $this->patchJson("/api/organizers/{$organizer->id}/events/evt-001", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event');

        $this->patchJson("/api/organizers/{$organizer->id}/events/evt-002", [
            'title' => 'Lintas Organizer',
        ])->assertNotFound();
    }

    public function test_terminal_event_status_cannot_be_reopened(): void
    {
        $organizer = $this->organizer();

        $this->actingAs($this->owner())
            ->patchJson("/api/organizers/{$organizer->id}/events/evt-001", [
                'status' => 'Cancelled',
            ])->assertOk()->assertJsonPath('data.status', 'Cancelled');

        $this->patchJson("/api/organizers/{$organizer->id}/events/evt-001", [
            'status' => 'Open',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_active_event_status_tracks_updated_capacity(): void
    {
        $organizer = $this->organizer();

        $this->actingAs($this->owner())
            ->patchJson("/api/organizers/{$organizer->id}/events/evt-001", [
                'quota' => 28,
            ])->assertOk()
            ->assertJsonPath('data.status', 'Nearly Full');

        $this->patchJson("/api/organizers/{$organizer->id}/events/evt-001", [
            'quota' => 27,
        ])->assertOk()
            ->assertJsonPath('data.status', 'Closed');
    }

    public function test_application_list_supports_filters_and_is_scoped_to_organizer(): void
    {
        $organizer = $this->organizer();
        $member = $this->createOrganizerUser($organizer, OrganizerMemberRole::Member);

        $this->actingAs($member)
            ->getJson("/api/organizers/{$organizer->id}/applications?q=Nadira&eventId=evt-001&status=Accepted&perPage=1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'app-001')
            ->assertJsonPath('data.0.volunteerProfile.name', 'Nadira Putri')
            ->assertJsonPath('meta.total', 1);

        $this->getJson("/api/organizers/{$organizer->id}/applications/app-001")
            ->assertOk()
            ->assertJsonPath('data.event.organizerId', $organizer->id);

        $this->getJson("/api/organizers/{$organizer->id}/applications/app-002")
            ->assertNotFound();

        $this->getJson("/api/organizers/{$organizer->id}/applications?eventId=evt-002")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('eventId');
    }

    public function test_only_manager_can_apply_valid_application_status_transitions(): void
    {
        $organizer = $this->organizer();
        $owner = $this->owner();
        $member = $this->createOrganizerUser($organizer, OrganizerMemberRole::Member);
        $application = $this->createSubmittedApplication($owner);
        $uri = "/api/organizers/{$organizer->id}/applications/{$application->id}/status";

        $this->actingAs($member)
            ->patchJson($uri, ['status' => 'Accepted'])
            ->assertForbidden();

        $this->actingAs($owner)
            ->patchJson($uri, ['status' => 'Accepted'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Accepted');

        $this->patchJson($uri, ['status' => 'Completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Completed');

        $this->assertDatabaseHas('volunteer_applications', [
            'id' => $application->id,
            'status' => 'Completed',
            'updated_by' => $owner->id,
        ]);
    }

    public function test_invalid_and_terminal_status_transitions_are_rejected(): void
    {
        $organizer = $this->organizer();
        $this->actingAs($this->owner());

        $this->patchJson("/api/organizers/{$organizer->id}/applications/app-001/status", [
            'status' => 'Submitted',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->patchJson("/api/organizers/{$organizer->id}/applications/app-003/status", [
            'status' => 'Accepted',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->patchJson("/api/organizers/{$organizer->id}/applications/app-004/status", [
            'status' => 'Accepted',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->patchJson("/api/organizers/{$organizer->id}/applications/app-001/status", [
            'status' => 'accepted',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    private function organizer(): Organizer
    {
        return Organizer::query()->findOrFail('org-aksara-muda');
    }

    private function owner(): User
    {
        return User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail();
    }

    private function createOrganizerUser(
        Organizer $organizer,
        OrganizerMemberRole $role
    ): User {
        $user = User::factory()->create();

        OrganizerMember::query()->create([
            'id' => 'mem-organizer-api-'.$role->value.'-'.$user->id,
            'organizer_id' => $organizer->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
    }

    private function createSubmittedApplication(User $owner): VolunteerApplication
    {
        $volunteer = User::factory()->create();
        $profile = VolunteerProfile::query()->create([
            'id' => 'usr-organizer-applicant',
            'user_id' => $volunteer->id,
            'name' => 'Applicant Organizer',
            'university' => 'Universitas Test',
            'major' => 'Administrasi Publik',
            'city' => 'Yogyakarta',
            'avatar_initials' => 'AO',
            'interests' => ['Pendidikan'],
        ]);

        $this->actingAs($owner);

        return VolunteerApplication::query()->create([
            'id' => 'app-organizer-test',
            'event_id' => 'evt-001',
            'volunteer_profile_id' => $profile->id,
            'role' => 'Education Mentor',
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => '2026-06-20',
            'motivation' => 'Membantu pelaksanaan kelas inspirasi.',
            'availability' => ['Full day'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(): array
    {
        return [
            'title' => 'Event Organizer Baru',
            'categoryId' => 'education',
            'location' => 'Ruang Komunitas',
            'city' => 'Yogyakarta',
            'mode' => 'Offline',
            'date' => '2026-08-10',
            'startTime' => '08:00',
            'endTime' => '12:00',
            'quota' => 30,
            'description' => 'Event untuk menguji alur pengelolaan organizer.',
            'benefits' => ['Sertifikat'],
            'skills' => ['Komunikasi'],
            'roles' => ['Education Mentor'],
        ];
    }
}
