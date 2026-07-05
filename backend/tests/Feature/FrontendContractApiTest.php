<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendContractApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    public function test_frontend_demo_accounts_can_login_by_area(): void
    {
        $this->withSpaHeaders();
        $this->get('/sanctum/csrf-cookie')->assertNoContent();

        $this->postJson('/api/auth/login', [
            'email' => 'nadira.putri@mail.com',
            'password' => 'prototype123',
            'accountType' => 'volunteer',
        ])->assertOk()
            ->assertJsonPath('data.capabilities.volunteer', true)
            ->assertJsonPath('data.capabilities.organizer', false)
            ->assertJsonPath('data.capabilities.admin', false);

        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->withSpaHeaders();
        $this->get('/sanctum/csrf-cookie')->assertNoContent();

        $this->postJson('/api/auth/login', [
            'email' => 'bagus.setiawan@mail.com',
            'password' => 'prototype123',
            'accountType' => 'organizer',
        ])->assertOk()
            ->assertJsonPath('data.capabilities.volunteer', false)
            ->assertJsonPath('data.capabilities.organizer', true)
            ->assertJsonPath('data.capabilities.manageOrganizer', true)
            ->assertJsonPath('data.capabilities.admin', false);

        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->withSpaHeaders();
        $this->get('/sanctum/csrf-cookie')->assertNoContent();

        $this->postJson('/api/auth/login', [
            'email' => 'admin@migunani.id',
            'password' => 'prototype123',
            'accountType' => 'admin',
        ])->assertOk()
            ->assertJsonPath('data.capabilities.volunteer', false)
            ->assertJsonPath('data.capabilities.organizer', false)
            ->assertJsonPath('data.capabilities.admin', true);
    }

    public function test_admin_contract_returns_frontend_stats_users_events_and_organizers(): void
    {
        $admin = User::query()->where('email', 'admin@migunani.id')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'stats' => [['id', 'label', 'value', 'helper']],
                    'users' => [['id', 'name', 'email', 'role', 'status', 'city', 'joinedAt', 'avatarInitials']],
                    'events' => [['id', 'slug', 'title', 'category', 'organizerId', 'status']],
                    'organizers' => [['id', 'name', 'type', 'city', 'verified', 'logoInitial']],
                ],
            ]);

        $this->actingAs(User::query()->where('email', 'nadira@example.com')->firstOrFail())
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_register_creates_frontend_role_specific_accounts(): void
    {
        $this->withSpaHeaders();
        $this->get('/sanctum/csrf-cookie')->assertNoContent();

        $this->postJson('/api/auth/register', [
            'role' => 'volunteer',
            'name' => 'Relawan Baru',
            'email' => 'relawan.baru@example.test',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
            'city' => 'Yogyakarta',
            'university' => 'Komunitas Baru',
            'major' => 'Sosial',
            'interests' => ['Pendidikan', 'Sosial'],
        ])->assertCreated()
            ->assertJsonPath('data.capabilities.volunteer', true)
            ->assertJsonPath('data.volunteerProfile.name', 'Relawan Baru');

        $this->assertDatabaseHas('users', [
            'email' => 'relawan.baru@example.test',
            'role' => 'volunteer',
        ]);

        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->withSpaHeaders();
        $this->get('/sanctum/csrf-cookie')->assertNoContent();

        $this->postJson('/api/auth/register', [
            'role' => 'organizer',
            'name' => 'Komunitas Baru',
            'organizationName' => 'Komunitas Baru',
            'organizationType' => 'Komunitas',
            'email' => 'organizer.baru@example.test',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
            'city' => 'Sleman',
        ])->assertCreated()
            ->assertJsonPath('data.capabilities.organizer', true)
            ->assertJsonPath('data.capabilities.manageOrganizer', true);

        $this->assertDatabaseHas('users', [
            'email' => 'organizer.baru@example.test',
            'role' => 'organizer',
        ]);
        $this->assertDatabaseHas('organizers', [
            'name' => 'Komunitas Baru',
            'verified' => false,
        ]);
    }

    public function test_organizer_can_check_in_applicant(): void
    {
        $owner = User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail();

        $this->actingAs($owner)
            ->patchJson('/api/organizers/org-aksara-muda/applications/app-001/check-in')
            ->assertOk()
            ->assertJsonPath('data.id', 'app-001')
            ->assertJsonPath('data.checkedInBy', $owner->id)
            ->assertJsonStructure(['data' => ['checkedInAt']]);

        $this->assertDatabaseHas('volunteer_applications', [
            'id' => 'app-001',
            'checked_in_by' => $owner->id,
        ]);
    }

    private function withSpaHeaders(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }
}
