<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicCertificateResource;
use App\Models\Certificate;

class PublicCertificateController extends Controller
{
    public function show(string $credentialId): PublicCertificateResource
    {
        $certificate = Certificate::query()
            ->with('supersededBy')
            ->whereRaw('UPPER(credential_id) = ?', [strtoupper($credentialId)])
            ->firstOrFail();

        return new PublicCertificateResource($certificate);
    }
}
