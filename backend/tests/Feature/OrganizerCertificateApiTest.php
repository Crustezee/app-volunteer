<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\OrganizerMemberRole;
use App\Models\Organizer;
use App\Models\OrganizerMember;
use App\Models\User;
use App\Models\VolunteerApplication;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerCertificateApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    public function test_owner_can_issue_certificate_with_immutable_snapshot_and_notification(): void
    {
        $application = $this->completedApplication();
        $owner = $this->owner();
        $volunteer = $this->volunteer();

        $response = $this->actingAs($owner)
            ->postJson('/api/organizers/org-aksara-muda/applications/app-004/certificate', [
                'hours' => 4,
                'issuedAt' => '2026-06-29',
            ])
            ->assertCreated()
            ->assertJsonPath('data.applicationId', $application->id)
            ->assertJsonPath('data.status', 'Issued')
            ->assertJsonPath('data.revisionNumber', 1)
            ->assertJsonPath('data.snapshot.volunteerName', 'Nadira Putri')
            ->assertJsonPath('data.snapshot.eventTitle', 'Mentoring Beasiswa untuk SMA')
            ->assertJsonPath('data.snapshot.organizerName', 'Aksara Muda Foundation');

        $credentialId = $response->json('data.credentialId');
        $certificateId = $response->json('data.id');

        $this->assertMatchesRegularExpression('/^MGN-2026-[A-Z0-9]{10}$/', $credentialId);
        $this->assertDatabaseHas('certificates', [
            'id' => $certificateId,
            'application_id' => $application->id,
            'status' => 'Issued',
            'revision_number' => 1,
            'created_by' => $owner->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $volunteer->id,
        ]);

        $dashboard = $this->actingAs($volunteer)->getJson('/api/volunteer/dashboard')
            ->assertOk()
            ->assertJsonPath('data.notifications.0.kind', 'certificate_issued');
        $notificationId = $dashboard->json('data.notifications.0.id');
        $this->patchJson("/api/notifications/{$notificationId}/read")
            ->assertNoContent();
        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
            'read_at' => null,
        ]);

        $this->getJson("/api/certificates/verify/{$credentialId}")
            ->assertOk()
            ->assertJsonPath('data.isValid', true)
            ->assertJsonPath('data.volunteerName', 'Nadira Putri')
            ->assertJsonMissingPath('data.applicationId');

        $this->actingAs($owner);

        $this->postJson('/api/organizers/org-aksara-muda/applications/app-004/certificate', [
            'hours' => 4,
        ])->assertUnprocessable()->assertJsonValidationErrors('applicationId');
    }

    public function test_organizer_certificate_list_is_paginated_filterable_and_scoped(): void
    {
        $this->actingAs($this->owner());

        $this->getJson('/api/organizers/org-aksara-muda/certificates?eventId=evt-006&status=Issued&q=Nadira&perPage=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'crt-001')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/organizers/org-aksara-muda/certificates?eventId=evt-002')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('eventId');

        $member = $this->createMember(OrganizerMemberRole::Member);
        $this->actingAs($member)
            ->getJson('/api/organizers/org-aksara-muda/certificates')
            ->assertOk();

        $this->postJson('/api/organizers/org-aksara-muda/applications/app-004/certificate', [
            'hours' => 4,
        ])->assertForbidden();
    }

    public function test_revoke_and_revision_use_new_credential_and_public_verification_links_replacement(): void
    {
        $this->completedApplication();
        $owner = $this->owner();
        $this->actingAs($owner);

        $issued = $this->postJson('/api/organizers/org-aksara-muda/applications/app-004/certificate', [
            'hours' => 4,
        ])->assertCreated();
        $oldId = $issued->json('data.id');
        $oldCredential = $issued->json('data.credentialId');

        $this->patchJson("/api/organizers/org-aksara-muda/certificates/{$oldId}/revoke", [
            'reason' => 'Jumlah jam kontribusi perlu diperbaiki.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'Revoked')
            ->assertJsonPath('data.revocationReason', 'Jumlah jam kontribusi perlu diperbaiki.');

        $this->actingAs($this->volunteer())
            ->get("/api/volunteer/certificates/{$oldId}/download")
            ->assertConflict();

        $this->actingAs($owner);

        $revision = $this->postJson('/api/organizers/org-aksara-muda/applications/app-004/certificate', [
            'hours' => 5,
            'supersedesCertificateId' => $oldId,
        ])->assertCreated()
            ->assertJsonPath('data.revisionNumber', 2)
            ->assertJsonPath('data.supersedesCertificateId', $oldId);
        $newCredential = $revision->json('data.credentialId');

        $this->assertNotSame($oldCredential, $newCredential);
        $this->getJson("/api/certificates/verify/{$oldCredential}")
            ->assertOk()
            ->assertJsonPath('data.isValid', false)
            ->assertJsonPath('data.status', 'Revoked')
            ->assertJsonPath('data.replacementCredentialId', $newCredential);
        $this->getJson("/api/certificates/verify/{$newCredential}")
            ->assertOk()
            ->assertJsonPath('data.isValid', true)
            ->assertJsonPath('data.revisionNumber', 2);
        $this->actingAs($this->volunteer())
            ->getJson('/api/volunteer/dashboard')
            ->assertOk()
            ->assertJsonPath('data.profile.certificates', 4)
            ->assertJsonPath('data.profile.totalHours', 21);

        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_invalid_public_credential_and_cross_organizer_certificate_are_hidden(): void
    {
        $this->getJson('/api/certificates/verify/DOES-NOT-EXIST')->assertNotFound();

        $this->actingAs($this->owner())
            ->getJson('/api/organizers/org-aksara-muda/certificates/crt-002')
            ->assertNotFound();
    }

    private function completedApplication(): VolunteerApplication
    {
        $application = VolunteerApplication::query()->findOrFail('app-004');
        $application->update(['status' => ApplicationStatus::Completed]);

        return $application->refresh();
    }

    private function owner(): User
    {
        return User::query()->where('email', 'owner@aksaramuda.test')->firstOrFail();
    }

    private function volunteer(): User
    {
        return User::query()->where('email', 'nadira@example.com')->firstOrFail();
    }

    private function createMember(OrganizerMemberRole $role): User
    {
        $user = User::factory()->create();
        OrganizerMember::query()->create([
            'id' => 'mem-certificate-'.$user->id,
            'organizer_id' => Organizer::query()->findOrFail('org-aksara-muda')->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
    }
}
