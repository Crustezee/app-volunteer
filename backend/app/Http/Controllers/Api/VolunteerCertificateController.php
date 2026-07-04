<?php

namespace App\Http\Controllers\Api;

use App\Enums\CertificateStatus;
use App\Exceptions\ConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CertificateIndexRequest;
use App\Http\Resources\CertificateCollection;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use App\Services\CurrentVolunteerProfile;
use App\Services\EventViewerContext;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class VolunteerCertificateController extends Controller
{
    public function index(
        CertificateIndexRequest $request,
        CurrentVolunteerProfile $currentVolunteer,
        EventViewerContext $viewerContext
    ): CertificateCollection {
        $filters = $request->validated();
        $profile = $currentVolunteer->resolve($request->user());
        $certificates = Certificate::query()
            ->with(['application.event.category', 'application.event.organizer', 'application.volunteerProfile', 'supersededBy'])
            ->whereHas(
                'application',
                fn (Builder $query) => $query->where('volunteer_profile_id', $profile->id)
            )
            ->orderByDesc('issued_at')
            ->orderBy('id')
            ->paginate($filters['perPage'] ?? 12)
            ->withQueryString();

        $viewerContext->apply(
            new Collection(
                $certificates->getCollection()
                    ->map(fn (Certificate $certificate) => $certificate->application->event)
                    ->all()
            ),
            $profile
        );

        return new CertificateCollection($certificates);
    }

    public function show(
        Request $request,
        Certificate $certificate,
        EventViewerContext $viewerContext
    ): CertificateResource {
        $certificate->load([
            'application.event.category',
            'application.event.organizer',
            'application.volunteerProfile',
        ]);
        $this->authorize('view', $certificate);

        $viewerContext->apply(
            new Collection([$certificate->application->event]),
            $request->user()->volunteerProfile
        );

        return new CertificateResource($certificate);
    }

    public function download(Certificate $certificate): Response
    {
        $certificate->load('application.volunteerProfile');
        $this->authorize('view', $certificate);

        if ($certificate->status !== CertificateStatus::Issued) {
            throw new ConflictException('Sertifikat telah dicabut dan tidak dapat diunduh.');
        }

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('certificates.pdf', [
            'certificate' => $certificate,
        ])->render(), 'UTF-8');
        $pdf->setPaper('a4', 'landscape');
        $pdf->render();

        $filename = 'sertifikat-'.Str::slug($certificate->credential_id).'.pdf';

        return response($pdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
