<?php

namespace App\Http\Requests;

use App\Enums\OrganizerMemberRole;
use App\Models\Certificate;
use App\Models\Organizer;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organizer = $this->route('organizer');
        $certificate = $this->route('certificate');

        return $organizer instanceof Organizer
            && $certificate instanceof Certificate
            && $certificate->application()->whereHas(
                'event',
                fn ($query) => $query->where('organizer_id', $organizer->id)
            )->exists()
            && ($this->user()?->hasOrganizerRole($organizer, OrganizerMemberRole::managerValues()) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'min:10', 'max:500'],
        ];
    }
}
