<?php

namespace Tests\Feature;

use App\Enums\OrganizerMemberRole;
use App\Models\Organizer;
use App\Models\OrganizerMember;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerEvent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    public function test_sanctum_and_stateful_spa_configuration_are_available(): void
    {
        $this->assertTrue(Schema::hasTable('personal_access_tokens'));
        $this->assertContains(HasApiTokens::class, class_uses_recursive(User::class));
        $this->assertContains('localhost:5173', config('sanctum.stateful'));
        $this->assertTrue(config('cors.supports_credentials'));

        $this->withHeader('Origin', 'http://localhost:5173')
            ->get('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }

    public function test_resources_use_camel_case_and_do_not_leak_internal_fields(): void
    {
        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'description', 'color', 'bgColor']]])
            ->assertJsonMissingPath('data.0.bg_color')
            ->assertJsonMissingPath('data.0.created_by');

        $this->getJson('/api/organizers/org-aksara-muda')
            ->assertOk()
            ->assertJsonPath('data.logoInitial', 'A')
            ->assertJsonMissingPath('data.logo_initial')
            ->assertJsonMissingPath('data.users')
            ->assertJsonMissingPath('data.createdBy');
    }

    public function test_organizer_policy_distinguishes_owner_admin_member_and_outsider(): void
    {
        $organizer = Organizer::query()->findOrFail('org-aksara-muda');
        $event = VolunteerEvent::query()->findOrFail('evt-001');
        $application = VolunteerApplication::query()->findOrFail('app-001');
        $owner = User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail();
        $admin = $this->createOrganizerUser($organizer, OrganizerMemberRole::Admin);
        $member = $this->createOrganizerUser($organizer, OrganizerMemberRole::Member);
        $outsider = User::factory()->create();

        $this->assertTrue(Gate::forUser($owner)->allows('manage', $organizer));
        $this->assertTrue(Gate::forUser($admin)->allows('manage', $organizer));
        $this->assertFalse(Gate::forUser($member)->allows('manage', $organizer));
        $this->assertFalse(Gate::forUser($outsider)->allows('manage', $organizer));

        $this->assertTrue(Gate::forUser($owner)->allows('update', $event));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $event));
        $this->assertFalse(Gate::forUser($member)->allows('update', $event));
        $this->assertTrue(Gate::forUser($member)->allows('view', $application));
        $this->assertFalse(Gate::forUser($outsider)->allows('view', $application));
    }

    public function test_validation_and_not_found_errors_use_consistent_contract(): void
    {
        $this->actingAs(User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail());

        $this->postJson('/api/organizers/org-aksara-muda/events', [
            'title' => 'Payload Lama',
            'category_id' => 'education',
        ])->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors(['categoryId', 'location']);

        $this->getJson('/api/events/tidak-ada')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource tidak ditemukan.']);
    }

    public function test_authentication_and_authorization_errors_use_consistent_contract(): void
    {
        Route::get('/api/_test/unauthenticated', fn () => throw new AuthenticationException);
        Route::get('/api/_test/forbidden', fn () => throw new AuthorizationException);

        $this->getJson('/api/_test/unauthenticated')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $this->getJson('/api/_test/forbidden')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Akses ditolak.']);
    }

    public function test_unexpected_api_errors_do_not_expose_debug_trace(): void
    {
        Route::get('/api/_test/server-error', fn () => throw new \RuntimeException('Sensitive detail'));

        $this->getJson('/api/_test/server-error')
            ->assertInternalServerError()
            ->assertExactJson(['message' => 'Terjadi kesalahan pada server.'])
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('file')
            ->assertJsonMissingPath('trace');
    }

    public function test_api_rate_limiter_returns_normalized_error(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.60']);

        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->getJson('/api/health')->assertOk();
        }

        $this->getJson('/api/health')
            ->assertTooManyRequests()
            ->assertExactJson([
                'message' => 'Terlalu banyak request. Coba kembali beberapa saat lagi.',
            ]);
    }

    public function test_write_rate_limiter_is_stricter_than_read_limiter(): void
    {
        $this->actingAs(User::query()->where('email', 'nadira@example.com')->firstOrFail());
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.30']);

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->putJson('/api/volunteer/saved-events/evt-003')->assertOk();
        }

        $this->putJson('/api/volunteer/saved-events/evt-003')
            ->assertTooManyRequests()->assertExactJson([
                'message' => 'Terlalu banyak request. Coba kembali beberapa saat lagi.',
            ]);
    }

    private function createOrganizerUser(
        Organizer $organizer,
        OrganizerMemberRole $role
    ): User {
        $user = User::factory()->create();

        OrganizerMember::query()->create([
            'id' => 'mem-'.$role->value.'-'.$user->id,
            'organizer_id' => $organizer->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
    }
}
