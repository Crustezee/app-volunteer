<?php

namespace App\Http\Requests;

use App\Enums\CertificateStatus;
use App\Models\Organizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrganizerCertificateIndexRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('q')) {
            $this->merge(['q' => trim((string) $this->query('q'))]);
        }
    }

    public function authorize(): bool
    {
        $organizer = $this->route('organizer');

        return $organizer instanceof Organizer
            && ($this->user()?->belongsToOrganizer($organizer) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'eventId' => ['nullable', 'string', 'exists:volunteer_events,id'],
            'status' => ['nullable', Rule::enum(CertificateStatus::class)],
            'issuedFrom' => ['nullable', 'date'],
            'issuedTo' => ['nullable', 'date', 'after_or_equal:issuedFrom'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('eventId') || ! $this->filled('eventId')) {
                    return;
                }

                /** @var Organizer $organizer */
                $organizer = $this->route('organizer');

                if (! $organizer->events()->whereKey($this->string('eventId')->toString())->exists()) {
                    $validator->errors()->add('eventId', 'Event bukan milik organizer ini.');
                }
            },
        ];
    }
}
