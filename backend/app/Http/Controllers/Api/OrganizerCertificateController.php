<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizerCertificateIndexRequest;
use App\Http\Requests\RevokeCertificateRequest;
use App\Http\Requests\StoreCertificateRequest;
use App\Http\Resources\CertificateCollection;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use App\Models\Organizer;
use App\Models\VolunteerApplication;
use App\Services\CertificateIssuer;
use App\Services\CertificateRevoker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizerCertificateController extends Controller
{
    public function index(
        OrganizerCertificateIndexRequest $request,
        Organizer $organizer
    ): CertificateCollection {
        $filters = $request->validated();
        $certificates = Certificate::query()
            ->with([
                'application.event.category',
                'application.event.organizer',
                'application.volunteerProfile',
                'supersedes',
                'supersededBy',
            ])
            ->whereHas(
                'application.event',
                fn (Builder $query) => $query->where('organizer_id', $organizer->id)
            )
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $term = "%{$search}%";
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('credential_id', 'like', $term)
                        ->orWhere('volunteer_name_snapshot', 'like', $term)
                        ->orWhere('event_title_snapshot', 'like', $term);
                });
            })
            ->when(
                $filters['eventId'] ?? null,
                fn (Builder $query, string $eventId) => $query->whereHas(
                    'application',
                    fn (Builder $query) => $query->where('event_id', $eventId)
                )
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status) => $query->where('status', $status)
            )
            ->when(
                $filters['issuedFrom'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate('issued_at', '>=', $date)
            )
            ->when(
                $filters['issuedTo'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate('issued_at', '<=', $date)
            )
            ->orderByDesc('issued_at')
            ->orderByDesc('revision_number')
            ->paginate($filters['perPage'] ?? 15)
            ->withQueryString();

        return new CertificateCollection($certificates);
    }

    public function show(
        Request $request,
        Organizer $organizer,
        Certificate $certificate
    ): CertificateResource {
        $this->ensureCertificateBelongsToOrganizer($certificate, $organizer);
        abort_unless($request->user()?->belongsToOrganizer($organizer), 403);

        $certificate->load([
            'application.event.category',
            'application.event.organizer',
            'application.volunteerProfile',
            'supersedes',
            'supersededBy',
        ]);

        return new CertificateResource($certificate);
    }

    public function store(
        StoreCertificateRequest $request,
        Organizer $organizer,
        VolunteerApplication $application,
        CertificateIssuer $issuer
    ): JsonResponse {
        $certificate = $issuer->issue($application, $request->validated(), $request->user());

        return (new CertificateResource($certificate))
            ->response()
            ->setStatusCode(201);
    }

    public function revoke(
        RevokeCertificateRequest $request,
        Organizer $organizer,
        Certificate $certificate,
        CertificateRevoker $revoker
    ): CertificateResource {
        $this->ensureCertificateBelongsToOrganizer($certificate, $organizer);

        return new CertificateResource(
            $revoker->revoke($certificate, $request->validated('reason'), $request->user())
        );
    }

    private function ensureCertificateBelongsToOrganizer(
        Certificate $certificate,
        Organizer $organizer
    ): void {
        abort_unless(
            $certificate->application()->whereHas(
                'event',
                fn (Builder $query) => $query->where('organizer_id', $organizer->id)
            )->exists(),
            404
        );
    }
}
