<?php

namespace Tests\Feature;

use App\Enums\OrganizerMemberRole;
use App\Models\Organizer;
use App\Models\OrganizerMember;
use App\Models\User;
use App\Models\VolunteerProfile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    public function test_spa_can_login_read_current_user_and_logout(): void
    {
        $this->withSpaHeaders();

        $this->get('/sanctum/csrf-cookie')->assertNoContent();

        $this->postJson('/api/auth/login', [
            'email' => ' NADIRA@EXAMPLE.COM ',
            'password' => 'password',
            'accountType' => 'volunteer',
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'nadira@example.com')
            ->assertJsonPath('data.volunteerProfile.id', 'usr-nadira')
            ->assertJsonPath('data.capabilities.volunteer', true)
            ->assertJsonPath('data.capabilities.organizer', false)
            ->assertJsonPath('data.capabilities.manageOrganizer', false)
            ->assertJsonCount(0, 'data.organizers')
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.organizers.0.pivot');

        $this->assertAuthenticated('web');

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Nadira Putri');

        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->assertGuest('web');
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_organizer_account_logs_into_organizer_area_only(): void
    {
        $this->withSpaHeaders();

        $this->postJson('/api/auth/login', [
            'email' => 'owner@aksaramuda.test',
            'password' => 'password',
            'accountType' => 'organizer',
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'owner@aksaramuda.test')
            ->assertJsonMissingPath('data.volunteerProfile.id')
            ->assertJsonPath('data.capabilities.volunteer', false)
            ->assertJsonPath('data.capabilities.organizer', true)
            ->assertJsonPath('data.capabilities.manageOrganizer', true)
            ->assertJsonPath('data.organizers.0.memberRole', 'Owner')
            ->assertJsonCount(1, 'data.organizers');
    }

    public function test_login_rejects_account_that_does_not_match_selected_area(): void
    {
        $this->withSpaHeaders();

        $this->postJson('/api/auth/login', [
            'email' => 'nadira@example.com',
            'password' => 'password',
            'accountType' => 'organizer',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'Akun ini tidak terdaftar sebagai organizer.');

        $this->postJson('/api/auth/login', [
            'email' => 'owner@aksaramuda.test',
            'password' => 'password',
            'accountType' => 'volunteer',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'Akun ini tidak terdaftar sebagai relawan.');
    }

    public function test_invalid_credentials_return_generic_validation_error(): void
    {
        $this->withSpaHeaders();

        $this->postJson('/api/auth/login', [
            'email' => 'nadira@example.com',
            'password' => 'wrong-password',
            'accountType' => 'volunteer',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'Email atau password tidak valid.')
            ->assertJsonMissingPath('errors.password');

        $this->assertGuest('web');
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $this->withSpaHeaders();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.5']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/auth/login', [
                'email' => 'nadira@example.com',
                'password' => 'wrong-password',
                'accountType' => 'volunteer',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/auth/login', [
            'email' => 'nadira@example.com',
            'password' => 'wrong-password',
            'accountType' => 'volunteer',
        ])->assertTooManyRequests()
            ->assertExactJson([
                'message' => 'Terlalu banyak request. Coba kembali beberapa saat lagi.',
            ]);
    }

    public function test_sensitive_routes_require_authentication(): void
    {
        foreach ([
            ['GET', '/api/profile'],
            ['GET', '/api/volunteer/applications'],
            ['GET', '/api/volunteer/saved-events'],
            ['GET', '/api/volunteer/certificates'],
            ['GET', '/api/volunteer/dashboard'],
            ['GET', '/api/organizers/org-aksara-muda/dashboard'],
            ['POST', '/api/organizers/org-aksara-muda/events'],
            ['PUT', '/api/volunteer/saved-events/evt-001'],
            ['DELETE', '/api/volunteer/saved-events/evt-001'],
            ['POST', '/api/events/evt-005/applications'],
            ['PATCH', '/api/organizers/org-aksara-muda/applications/app-001/status'],
        ] as [$method, $uri]) {
            $this->json($method, $uri)
                ->assertUnauthorized()
                ->assertExactJson(['message' => 'Unauthenticated.']);
        }

        $this->getJson('/api/events')->assertOk();
        $this->getJson('/api/events/evt-001')->assertOk();
    }

    public function test_organizer_policy_is_enforced_by_write_and_dashboard_endpoints(): void
    {
        $organizer = Organizer::query()->findOrFail('org-aksara-muda');
        $member = $this->createOrganizerUser($organizer, OrganizerMemberRole::Member);
        $outsider = User::factory()->create();
        $payload = $this->eventPayload();

        $this->actingAs($member)
            ->postJson('/api/organizers/'.$organizer->id.'/events', $payload)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Akses ditolak.']);

        $this->actingAs($member)
            ->getJson('/api/organizers/'.$organizer->id.'/dashboard')
            ->assertOk();

        $this->actingAs($outsider)
            ->getJson('/api/organizers/'.$organizer->id.'/dashboard')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Akses ditolak.']);

        $owner = User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail();

        $response = $this->actingAs($owner)
            ->postJson('/api/organizers/'.$organizer->id.'/events', $payload)
            ->assertCreated()
            ->assertJsonPath('data.organizerId', $organizer->id);

        $this->assertDatabaseHas('volunteer_events', [
            'id' => $response->json('data.id'),
            'organizer_id' => $organizer->id,
            'created_by' => $owner->id,
        ]);
    }

    public function test_volunteer_endpoints_only_return_authenticated_users_data(): void
    {
        $otherUser = User::factory()->create();
        $otherProfile = VolunteerProfile::query()->create([
            'id' => 'usr-other',
            'user_id' => $otherUser->id,
            'name' => 'Volunteer Lain',
            'university' => 'Universitas Lain',
            'major' => 'Teknik',
            'city' => 'Bandung',
            'avatar_initials' => 'VL',
            'interests' => ['Lingkungan'],
        ]);

        $this->actingAs($otherUser)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $otherProfile->id);

        $this->getJson('/api/volunteer/applications')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/volunteer/certificates')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/volunteer/dashboard')
            ->assertOk()
            ->assertJsonPath('data.profile.id', $otherProfile->id)
            ->assertJsonCount(0, 'data.applications')
            ->assertJsonCount(0, 'data.certificates');
    }

    public function test_user_without_volunteer_profile_cannot_access_volunteer_area(): void
    {
        $organizerOnly = User::factory()->create();

        $this->actingAs($organizerOnly)
            ->getJson('/api/volunteer/dashboard')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Akses ditolak.']);

        $this->postJson('/api/events/evt-005/applications', [
            'role' => 'Community Facilitator',
            'motivation' => 'Saya ingin mengikuti kegiatan sebagai relawan.',
            'availability' => ['Full day'],
        ])->assertForbidden();
    }

    private function withSpaHeaders(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    private function createOrganizerUser(
        Organizer $organizer,
        OrganizerMemberRole $role
    ): User {
        $user = User::factory()->create();

        OrganizerMember::query()->create([
            'id' => 'mem-auth-'.$role->value.'-'.$user->id,
            'organizer_id' => $organizer->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(): array
    {
        return [
            'title' => 'Event dari Authentication Test',
            'categoryId' => 'education',
            'location' => 'Ruang Test',
            'city' => 'Yogyakarta',
            'mode' => 'Offline',
            'date' => '2026-08-01',
            'startTime' => '08:00',
            'endTime' => '12:00',
            'quota' => 20,
            'description' => 'Event untuk menguji policy organizer.',
            'benefits' => [],
            'skills' => [],
            'roles' => ['Field Volunteer'],
        ];
    }
}
