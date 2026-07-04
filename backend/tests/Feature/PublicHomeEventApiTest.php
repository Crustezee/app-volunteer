<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHomeEventApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    public function test_home_returns_aggregated_stats_categories_and_featured_events(): void
    {
        $response = $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.stats.eventCount', 8)
            ->assertJsonPath('data.stats.availableEvents', 7)
            ->assertJsonPath('data.stats.totalSlots', 408)
            ->assertJsonPath('data.stats.totalRegistered', 325)
            ->assertJsonPath('data.stats.categoryCount', 7)
            ->assertJsonPath('data.stats.organizerCount', 5)
            ->assertJsonCount(4, 'data.categories')
            ->assertJsonCount(3, 'data.featuredEvents');

        $this->assertTrue(
            collect($response->json('data.featuredEvents'))
                ->every(fn (array $event) => $event['featured'] && $event['isSaved'] === false)
        );
    }

    public function test_event_list_is_paginated_with_camel_case_metadata(): void
    {
        $response = $this->getJson('/api/events?sort=eventDate&perPage=3&page=2')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.currentPage', 2)
            ->assertJsonPath('meta.lastPage', 3)
            ->assertJsonPath('meta.perPage', 3)
            ->assertJsonPath('meta.total', 8)
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'previous', 'next'],
                'meta' => ['currentPage', 'from', 'lastPage', 'path', 'perPage', 'to', 'total'],
            ])
            ->assertJsonMissingPath('meta.current_page')
            ->assertJsonMissingPath('meta.per_page');

        $this->assertSame(
            ['evt-004', 'evt-005', 'evt-006'],
            collect($response->json('data'))->pluck('id')->all()
        );
    }

    public function test_event_filters_can_be_combined(): void
    {
        $this->getJson('/api/events?categoryId=education&mode=Online&status=Open&featured=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'evt-008')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/events?featured=true')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_event_search_covers_event_organizer_category_and_tags(): void
    {
        $this->getJson('/api/events?q=Hijau%20Kota%20Collective')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/events?q=Lingkungan')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'evt-002');

        $this->getJson('/api/events?q=Outdoor')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'evt-002');

        $this->getJson('/api/events?q=Mentoring%20Beasiswa%20untuk%20SMA')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'evt-008');
    }

    public function test_event_sort_modes_return_deterministic_results(): void
    {
        $this->getJson('/api/events?sort=remainingQuota')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'evt-008')
            ->assertJsonPath('data.0.remainingQuota', 16);

        $this->getJson('/api/events?sort=eventDate')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'evt-001');

        $this->getJson('/api/events?sort=latest')
            ->assertOk()
            ->assertJsonCount(8, 'data');
    }

    public function test_invalid_event_query_parameters_return_validation_errors(): void
    {
        $this->getJson('/api/events?categoryId=missing&mode=Field&status=Unknown&sort=oldest&perPage=51&page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'categoryId',
                'mode',
                'status',
                'sort',
                'perPage',
                'page',
            ]);
    }

    public function test_event_detail_returns_related_events_and_anonymous_context(): void
    {
        $this->getJson('/api/events/kelas-inspirasi-anak-kali-code')
            ->assertOk()
            ->assertJsonPath('data.id', 'evt-001')
            ->assertJsonPath('data.categoryId', 'education')
            ->assertJsonPath('data.organizer.id', 'org-aksara-muda')
            ->assertJsonPath('data.isSaved', false)
            ->assertJsonPath('data.myApplication', null)
            ->assertJsonCount(1, 'data.relatedEvents')
            ->assertJsonPath('data.relatedEvents.0.id', 'evt-008');
    }

    public function test_authenticated_volunteer_receives_saved_and_application_context(): void
    {
        $user = User::query()->where('email', 'nadira@example.com')->firstOrFail();
        $this->actingAs($user);

        $this->getJson('/api/events/evt-001')
            ->assertOk()
            ->assertJsonPath('data.isSaved', true)
            ->assertJsonPath('data.myApplication.id', 'app-001')
            ->assertJsonPath('data.myApplication.status', 'Accepted');

        $response = $this->getJson('/api/events?categoryId=education&sort=eventDate')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertTrue(
            collect($response->json('data'))->every(fn (array $event) => $event['isSaved'])
        );

        $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.featuredEvents.0.isSaved', true);
    }
}
