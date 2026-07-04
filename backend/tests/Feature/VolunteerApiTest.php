<?php

namespace Tests\Feature;

use App\Models\SavedEvent;
use App\Models\User;
use App\Models\VolunteerEvent;
use App\Models\VolunteerProfile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->where('email', 'nadira@example.com')->firstOrFail());
    }

    public function test_application_list_is_scoped_filterable_and_paginated(): void
    {
        $this->getJson('/api/volunteer/applications?perPage=2&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.currentPage', 2)
            ->assertJsonPath('meta.perPage', 2)
            ->assertJsonPath('meta.total', 6)
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'previous', 'next'],
                'meta' => ['currentPage', 'lastPage', 'perPage', 'total'],
            ]);

        $response = $this->getJson('/api/volunteer/applications?status=Completed')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);

        $this->assertTrue(
            collect($response->json('data'))
                ->every(fn (array $application) => $application['status'] === 'Completed')
        );
    }

    public function test_application_submission_uses_route_event_and_updates_capacity(): void
    {
        VolunteerEvent::query()->whereKey('evt-005')->update([
            'registered' => 32,
            'quota' => 36,
            'status' => 'Open',
        ]);

        $response = $this->postJson('/api/events/evt-005/applications', [
            'role' => 'Community Facilitator',
            'motivation' => 'Saya siap membantu fasilitasi kegiatan sampai selesai.',
            'availability' => ['Siap hadir dari awal kegiatan'],
        ])->assertCreated()
            ->assertJsonPath('data.eventId', 'evt-005')
            ->assertJsonPath('data.volunteerProfileId', 'usr-nadira')
            ->assertJsonPath('data.status', 'Submitted')
            ->assertJsonPath('data.event.isSaved', true);

        $this->assertDatabaseHas('volunteer_applications', [
            'id' => $response->json('data.id'),
            'event_id' => 'evt-005',
            'volunteer_profile_id' => 'usr-nadira',
        ]);
        $this->assertDatabaseHas('volunteer_events', [
            'id' => 'evt-005',
            'registered' => 33,
            'status' => 'Nearly Full',
        ]);
    }

    public function test_application_validation_uses_event_roles_and_frontend_constraints(): void
    {
        $this->postJson('/api/events/evt-005/applications', [
            'role' => 'Health Support',
            'motivation' => 'Pendek',
            'availability' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['role', 'motivation', 'availability']);

        $this->getJson('/api/volunteer/applications?status=Unknown&perPage=51')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'perPage']);
    }

    public function test_duplicate_and_closed_event_applications_return_conflict(): void
    {
        $this->postJson('/api/events/evt-001/applications', [
            'role' => 'Education Mentor',
            'motivation' => 'Saya ingin kembali mendaftar pada kegiatan pendidikan ini.',
            'availability' => ['Full day'],
        ])->assertConflict()
            ->assertExactJson(['message' => 'Volunteer sudah mendaftar pada event ini.']);

        $this->postJson('/api/events/evt-007/applications', [
            'role' => 'Field Volunteer',
            'motivation' => 'Saya ingin membantu pelaksanaan festival warga ini.',
            'availability' => ['Full day'],
        ])->assertConflict()
            ->assertExactJson(['message' => 'Event tidak lagi menerima pendaftaran.']);
    }

    public function test_saved_event_list_is_paginated_and_marks_every_event_saved(): void
    {
        $response = $this->getJson('/api/volunteer/saved-events?perPage=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.currentPage', 1)
            ->assertJsonPath('meta.perPage', 2)
            ->assertJsonPath('meta.total', 4);

        $this->assertTrue(
            collect($response->json('data'))->every(fn (array $event) => $event['isSaved'])
        );
    }

    public function test_saving_and_removing_event_are_idempotent_and_audited(): void
    {
        $user = User::query()->where('email', 'nadira@example.com')->firstOrFail();

        $this->putJson('/api/volunteer/saved-events/evt-003')
            ->assertOk()
            ->assertJsonPath('data.id', 'evt-003')
            ->assertJsonPath('data.isSaved', true);
        $this->putJson('/api/volunteer/saved-events/evt-003')->assertOk();

        $this->assertDatabaseCount('saved_events', 5);
        $this->assertDatabaseHas('saved_events', [
            'event_id' => 'evt-003',
            'volunteer_profile_id' => 'usr-nadira',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->deleteJson('/api/volunteer/saved-events/evt-003')->assertNoContent();
        $this->deleteJson('/api/volunteer/saved-events/evt-003')->assertNoContent();

        $this->assertDatabaseMissing('saved_events', [
            'event_id' => 'evt-003',
            'volunteer_profile_id' => 'usr-nadira',
        ]);
    }

    public function test_saved_events_are_isolated_between_volunteers(): void
    {
        $otherUser = User::factory()->create();
        $otherProfile = VolunteerProfile::query()->create([
            'id' => 'usr-volunteer-two',
            'user_id' => $otherUser->id,
            'name' => 'Volunteer Dua',
            'university' => 'Universitas Dua',
            'major' => 'Sosial',
            'city' => 'Bandung',
            'avatar_initials' => 'VD',
            'interests' => ['Sosial'],
        ]);
        SavedEvent::query()->create([
            'id' => 'sav-volunteer-two',
            'event_id' => 'evt-003',
            'volunteer_profile_id' => $otherProfile->id,
        ]);

        $this->actingAs($otherUser)
            ->getJson('/api/volunteer/saved-events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'evt-003');

        $owner = User::query()->where('email', 'nadira@example.com')->firstOrFail();
        $this->actingAs($owner)
            ->getJson('/api/volunteer/saved-events')
            ->assertOk()
            ->assertJsonPath('meta.total', 4);
    }

    public function test_legacy_volunteer_routes_are_not_available(): void
    {
        $this->getJson('/api/applications')->assertNotFound();
        $this->postJson('/api/applications')->assertNotFound();
        $this->patchJson('/api/profile/saved-events')->assertNotFound();
    }
}
