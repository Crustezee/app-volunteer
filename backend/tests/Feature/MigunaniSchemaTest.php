<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VolunteerEvent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigunaniSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->where('email', 'nadira@example.com')->firstOrFail());
    }

    public function test_business_tables_use_normalized_schema_and_audit_columns(): void
    {
        foreach ([
            'users',
            'categories',
            'organizers',
            'volunteer_profiles',
            'organizer_members',
            'volunteer_events',
            'volunteer_applications',
            'saved_events',
            'certificates',
        ] as $table) {
            $this->assertTrue(
                Schema::hasColumns($table, ['id', 'created_at', 'created_by', 'updated_at', 'updated_by']),
                "Audit columns are incomplete on {$table}."
            );
        }

        $this->assertTrue(Schema::hasColumns('volunteer_profiles', ['user_id']));
        $this->assertTrue(Schema::hasColumns('volunteer_events', ['category_id', 'organizer_id']));
        $this->assertTrue(Schema::hasColumns('volunteer_applications', ['event_id', 'volunteer_profile_id']));
        $this->assertTrue(Schema::hasColumns('notifications', ['id', 'created_at', 'created_by', 'updated_at', 'updated_by']));
        $this->assertTrue(Schema::hasColumns('certificates', ['application_id', 'status', 'revision_number', 'supersedes_certificate_id', 'volunteer_name_snapshot', 'event_title_snapshot', 'organizer_name_snapshot', 'role_snapshot', 'event_date_snapshot']));
        $this->assertFalse(Schema::hasColumn('volunteer_profiles', 'saved_event_ids'));
        $this->assertFalse(Schema::hasTable('dashboard_stats'));
        $this->assertFalse(Schema::hasTable('organizer_metrics'));
    }

    public function test_api_preserves_category_name_and_calculates_dashboard_stats(): void
    {
        $this->getJson('/api/events/evt-001')
            ->assertOk()
            ->assertJsonPath('data.categoryId', 'education')
            ->assertJsonPath('data.category', 'Pendidikan')
            ->assertJsonPath('data.organizerId', 'org-aksara-muda')
            ->assertJsonMissingPath('data.created_by')
            ->assertJsonMissingPath('data.createdBy');

        $this->getJson('/api/volunteer/dashboard')
            ->assertOk()
            ->assertJsonPath('data.profile.totalHours', 16)
            ->assertJsonPath('data.profile.completedEvents', 3)
            ->assertJsonPath('data.profile.certificates', 3)
            ->assertJsonCount(4, 'data.profile.savedEventIds')
            ->assertJsonCount(4, 'data.stats');
    }

    public function test_saved_event_endpoint_is_idempotent(): void
    {
        $this->putJson('/api/volunteer/saved-events/evt-003')->assertOk();
        $this->putJson('/api/volunteer/saved-events/evt-003')->assertOk();

        $this->assertDatabaseCount('saved_events', 5);

        $this->deleteJson('/api/volunteer/saved-events/evt-003')->assertNoContent();

        $this->assertDatabaseMissing('saved_events', [
            'event_id' => 'evt-003',
            'volunteer_profile_id' => 'usr-nadira',
        ]);
    }

    public function test_application_is_linked_to_profile_and_duplicate_is_rejected(): void
    {
        $registered = VolunteerEvent::query()->findOrFail('evt-005')->registered;
        $payload = [
            'role' => 'Community Facilitator',
            'motivation' => 'Saya siap membantu kegiatan.',
            'availability' => ['Full day'],
        ];

        $this->postJson('/api/events/evt-005/applications', $payload)
            ->assertCreated()
            ->assertJsonPath('data.volunteerProfileId', 'usr-nadira');

        $this->assertDatabaseHas('volunteer_events', [
            'id' => 'evt-005',
            'registered' => $registered + 1,
        ]);

        $this->postJson('/api/events/evt-005/applications', $payload)
            ->assertConflict()
            ->assertJsonPath('message', 'Volunteer sudah mendaftar pada event ini.');
    }

    public function test_status_and_mode_are_validated_by_enum(): void
    {
        $this->actingAs(User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail());

        $this->patchJson('/api/organizers/org-aksara-muda/applications/app-001/status', ['status' => 'accepted'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->postJson('/api/organizers/org-aksara-muda/events', [
            'title' => 'Invalid Mode Event',
            'categoryId' => 'education',
            'location' => 'Test',
            'city' => 'Yogyakarta',
            'mode' => 'Field',
            'date' => '2026-07-10',
            'startTime' => '08:00',
            'endTime' => '12:00',
            'quota' => 20,
            'description' => 'Event pengujian mode.',
            'benefits' => [],
            'skills' => [],
            'roles' => ['Field Volunteer'],
        ])->assertUnprocessable()->assertJsonValidationErrors('mode');
    }

    public function test_certificate_requires_application_to_remain_completed(): void
    {
        $this->actingAs(User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail());

        $this->patchJson('/api/organizers/org-aksara-muda/applications/app-003/status', ['status' => 'Accepted'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('volunteer_applications', [
            'id' => 'app-003',
            'status' => 'Completed',
        ]);
    }

    public function test_authenticated_writes_populate_audit_users(): void
    {
        $user = User::query()->where('email', 'nadira@example.com')->firstOrFail();

        $this->actingAs($user)
            ->putJson('/api/volunteer/saved-events/evt-003')
            ->assertOk();

        $this->assertDatabaseHas('saved_events', [
            'event_id' => 'evt-003',
            'volunteer_profile_id' => 'usr-nadira',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_organizer_dashboard_only_contains_its_applications(): void
    {
        $this->actingAs(User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail());

        $response = $this->getJson('/api/organizers/org-aksara-muda/dashboard')
            ->assertOk()
            ->assertJsonCount(3, 'data.applications');

        $eventIds = collect($response->json('data.applications'))->pluck('eventId');

        $this->assertTrue($eventIds->every(fn (string $eventId) => in_array($eventId, ['evt-001', 'evt-006', 'evt-008'], true)));
    }
}
