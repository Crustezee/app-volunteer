<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VolunteerEvent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackendQaAuditTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    public function test_login_without_stateful_spa_session_returns_controlled_error_not_server_error(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'admin@migunani.id',
            'password' => 'prototype123',
        ])->assertStatus(419)
            ->assertJsonPath('message', 'Session SPA belum tersedia. Panggil /sanctum/csrf-cookie dari frontend terlebih dahulu.');
    }

    public function test_admin_collection_endpoints_are_admin_only_and_match_frontend_contract(): void
    {
        $admin = User::query()->where('email', 'admin@migunani.id')->firstOrFail();
        $organizer = User::query()->where('email', 'bagus.setiawan@mail.com')->firstOrFail();

        foreach (['/api/admin/users', '/api/admin/events', '/api/admin/organizers'] as $uri) {
            $this->actingAs($organizer)
                ->getJson($uri)
                ->assertForbidden()
                ->assertExactJson(['message' => 'Akses ditolak.']);
        }

        $this->actingAs($admin)
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonFragment([
                'email' => 'admin@migunani.id',
                'role' => 'admin',
                'status' => 'Active',
                'avatarInitials' => 'AM',
            ]);

        $this->getJson('/api/admin/events')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'title', 'category', 'organizerId', 'status']],
            ]);

        $this->getJson('/api/admin/organizers')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'type', 'city', 'verified', 'logoInitial']],
            ]);
    }

    public function test_notification_read_is_scoped_to_authenticated_user_and_idempotent(): void
    {
        $owner = User::query()->where('email', 'nadira@example.com')->firstOrFail();
        $other = User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail();
        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'qa.notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'data' => json_encode(['message' => 'QA notification']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($other)
            ->patchJson("/api/notifications/{$notificationId}/read")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource tidak ditemukan.']);

        $this->actingAs($owner)
            ->patchJson("/api/notifications/{$notificationId}/read")
            ->assertNoContent();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
            'read_at' => null,
        ]);

        $this->patchJson("/api/notifications/{$notificationId}/read")
            ->assertNoContent();
    }

    public function test_notification_list_and_read_all_are_scoped_to_authenticated_user(): void
    {
        $owner = User::query()->where('email', 'nadira@example.com')->firstOrFail();
        $other = User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail();
        $ownerNotificationId = (string) Str::uuid();
        $otherNotificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            [
                'id' => $ownerNotificationId,
                'type' => 'qa.notification',
                'notifiable_type' => User::class,
                'notifiable_id' => $owner->id,
                'data' => json_encode([
                    'kind' => 'accepted',
                    'title' => 'Aplikasi diterima',
                    'description' => 'Kelas Inspirasi siap masuk briefing.',
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $otherNotificationId,
                'type' => 'qa.notification',
                'notifiable_type' => User::class,
                'notifiable_id' => $other->id,
                'data' => json_encode(['message' => 'Other notification']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($owner)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownerNotificationId)
            ->assertJsonPath('data.0.kind', 'accepted')
            ->assertJsonPath('data.0.title', 'Aplikasi diterima')
            ->assertJsonPath('data.0.description', 'Kelas Inspirasi siap masuk briefing.')
            ->assertJsonMissing(['id' => $otherNotificationId]);

        $this->patchJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseMissing('notifications', [
            'id' => $ownerNotificationId,
            'read_at' => null,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotificationId,
            'read_at' => null,
        ]);
    }

    public function test_volunteer_can_cancel_own_open_application_only(): void
    {
        $volunteer = User::query()->where('email', 'nadira@example.com')->firstOrFail();
        $other = User::query()->where('email', 'bagus.setiawan@mail.com')->firstOrFail();
        $registeredBefore = VolunteerEvent::query()->findOrFail('evt-002')->registered;

        $this->actingAs($other)
            ->patchJson('/api/volunteer/applications/app-002/cancel')
            ->assertForbidden();

        $this->actingAs($volunteer)
            ->patchJson('/api/volunteer/applications/app-003/cancel')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->patchJson('/api/volunteer/applications/app-002/cancel')
            ->assertOk()
            ->assertJsonPath('data.id', 'app-002')
            ->assertJsonPath('data.status', 'Cancelled');

        $this->assertDatabaseHas('volunteer_applications', [
            'id' => 'app-002',
            'status' => 'Cancelled',
        ]);
        $this->assertSame(
            $registeredBefore - 1,
            VolunteerEvent::query()->findOrFail('evt-002')->registered
        );
    }

    public function test_admin_moderation_endpoints_are_admin_only_and_validate_payloads(): void
    {
        $admin = User::query()->where('email', 'admin@migunani.id')->firstOrFail();
        $organizer = User::query()->where('email', 'bagus.setiawan@mail.com')->firstOrFail();
        $volunteer = User::query()->where('email', 'nadira.putri@mail.com')->firstOrFail();

        $this->actingAs($organizer)
            ->patchJson('/api/admin/users/'.$organizer->id.'/status', ['status' => 'Suspended'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patchJson('/api/admin/users/'.$organizer->id.'/status', ['status' => 'Blocked'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->patchJson('/api/admin/users/'.$organizer->id.'/status', ['status' => 'Suspended'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Suspended');

        $this->patchJson('/api/admin/users/usr-'.$organizer->id.'/status', ['status' => 'Active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Active');

        $this->patchJson('/api/admin/users/usr-nadira-frontend/status', ['status' => 'Inactive'])
            ->assertOk()
            ->assertJsonPath('data.id', 'usr-nadira-frontend')
            ->assertJsonPath('data.status', 'Inactive');

        $this->patchJson('/api/admin/organizers/org-dapur-warga/verification', ['verified' => true])
            ->assertOk()
            ->assertJsonPath('data.verified', true);

        $this->patchJson('/api/admin/events/evt-001/status', ['status' => 'Closed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Closed');

        $this->patchJson('/api/admin/events/evt-001/status', ['status' => 'Completed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('users', [
            'id' => $organizer->id,
            'status' => 'Active',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $volunteer->id,
            'status' => 'Inactive',
        ]);
        $this->assertDatabaseHas('organizers', [
            'id' => 'org-dapur-warga',
            'verified' => true,
        ]);
        $this->assertDatabaseHas('volunteer_events', [
            'id' => 'evt-001',
            'status' => 'Closed',
        ]);
    }

    public function test_organizer_check_in_enforces_manager_role_and_organizer_scope(): void
    {
        $owner = User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail();
        $member = User::factory()->create();

        DB::table('organizer_members')->insert([
            'id' => 'mem-qa-member-'.$member->id,
            'organizer_id' => 'org-aksara-muda',
            'user_id' => $member->id,
            'role' => 'Member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($member)
            ->patchJson('/api/organizers/org-aksara-muda/applications/app-001/check-in')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Akses ditolak.']);

        $this->actingAs($owner)
            ->patchJson('/api/organizers/org-aksara-muda/applications/app-002/check-in')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource tidak ditemukan.']);

        $this->patchJson('/api/organizers/org-aksara-muda/applications/app-001/check-in')
            ->assertOk()
            ->assertJsonPath('data.checkedInBy', $owner->id);
    }
}
