<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerProfile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerDashboardCertificateApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->where('email', 'nadira@example.com')->firstOrFail());
    }

    public function test_dashboard_returns_consistent_aggregates_and_related_data(): void
    {
        $response = $this->getJson('/api/volunteer/dashboard')
            ->assertOk()
            ->assertJsonPath('data.profile.id', 'usr-nadira')
            ->assertJsonPath('data.profile.totalHours', 16)
            ->assertJsonPath('data.profile.completedEvents', 3)
            ->assertJsonPath('data.profile.certificates', 3)
            ->assertJsonCount(4, 'data.profile.savedEventIds')
            ->assertJsonCount(4, 'data.stats')
            ->assertJsonCount(6, 'data.applications')
            ->assertJsonCount(3, 'data.certificates')
            ->assertJsonCount(4, 'data.savedEvents');

        $savedIds = collect($response->json('data.savedEvents'))->pluck('id');

        $this->assertTrue($savedIds->contains('evt-001'));
        $this->assertTrue(
            collect($response->json('data.savedEvents'))
                ->every(fn (array $event) => $event['isSaved'])
        );
    }

    public function test_certificate_list_is_scoped_and_paginated(): void
    {
        $this->createOtherVolunteersCertificate();

        $this->getJson('/api/volunteer/certificates?perPage=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 'crt-001')
            ->assertJsonPath('data.0.eventId', 'evt-006')
            ->assertJsonPath('data.0.credentialId', 'MGN-2026-LIT-0424')
            ->assertJsonPath('meta.currentPage', 1)
            ->assertJsonPath('meta.perPage', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure([
                'links' => ['first', 'last', 'previous', 'next'],
                'meta' => ['currentPage', 'lastPage', 'perPage', 'total'],
            ]);

        $this->getJson('/api/volunteer/certificates?perPage=51')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('perPage');
    }

    public function test_certificate_detail_contains_event_and_volunteer(): void
    {
        $this->getJson('/api/volunteer/certificates/crt-001')
            ->assertOk()
            ->assertJsonPath('data.id', 'crt-001')
            ->assertJsonPath('data.applicationId', 'app-003')
            ->assertJsonPath('data.event.id', 'evt-006')
            ->assertJsonPath('data.event.organizer.id', 'org-aksara-muda')
            ->assertJsonPath('data.volunteerProfile.id', 'usr-nadira')
            ->assertJsonPath('data.hours', 5)
            ->assertJsonMissingPath('data.createdBy');
    }

    public function test_certificate_download_returns_private_pdf_attachment(): void
    {
        $response = $this->get('/api/volunteer/certificates/crt-001/download')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload('sertifikat-mgn-2026-lit-0424.pdf');

        $content = $response->getContent();
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertIsString($content);
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(1000, strlen($content));
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_certificate_owner_policy_prevents_cross_user_access(): void
    {
        [$otherUser] = $this->createOtherVolunteersCertificate();

        $this->getJson('/api/volunteer/certificates/crt-other')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Akses ditolak.']);

        $this->get('/api/volunteer/certificates/crt-other/download')
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->getJson('/api/volunteer/certificates')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'crt-other')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/volunteer/certificates/crt-other')
            ->assertOk()
            ->assertJsonPath('data.volunteerProfile.id', 'usr-certificate-owner');
    }

    public function test_legacy_dashboard_and_certificate_routes_are_removed(): void
    {
        $this->getJson('/api/dashboard/volunteer')->assertNotFound();
        $this->getJson('/api/certificates')->assertNotFound();
    }

    /**
     * @return array{User, VolunteerProfile}
     */
    private function createOtherVolunteersCertificate(): array
    {
        $user = User::factory()->create();
        $profile = VolunteerProfile::query()->create([
            'id' => 'usr-certificate-owner',
            'user_id' => $user->id,
            'name' => 'Pemilik Sertifikat Lain',
            'university' => 'Universitas Lain',
            'major' => 'Teknik',
            'city' => 'Bandung',
            'avatar_initials' => 'PSL',
            'interests' => ['Kesehatan'],
        ]);
        VolunteerApplication::query()->create([
            'id' => 'app-other-completed',
            'event_id' => 'evt-005',
            'volunteer_profile_id' => $profile->id,
            'role' => 'Community Facilitator',
            'status' => 'Completed',
            'submitted_at' => '2026-05-01',
            'motivation' => 'Membantu kegiatan sampai selesai untuk memperoleh sertifikat.',
            'availability' => ['Full day'],
        ]);
        Certificate::query()->create([
            'id' => 'crt-other',
            'application_id' => 'app-other-completed',
            'issued_at' => '2026-05-20',
            'credential_id' => 'MGN-OTHER-001',
            'hours' => 6,
        ]);

        return [$user, $profile];
    }
}
