<?php

namespace App\Http\Requests;

use App\Enums\OrganizerMemberRole;
use App\Models\Organizer;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organizer = $this->route('organizer');
        $application = $this->route('application');

        return $organizer instanceof Organizer
            && $application instanceof VolunteerApplication
            && $application->event()->where('organizer_id', $organizer->id)->exists()
            && ($this->user()?->hasOrganizerRole($organizer, OrganizerMemberRole::managerValues()) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'hours' => ['required', 'integer', 'min:1', 'max:1000'],
            'issuedAt' => ['nullable', 'date'],
            'supersedesCertificateId' => ['nullable', 'string', 'exists:certificates,id'],
        ];
    }
}
