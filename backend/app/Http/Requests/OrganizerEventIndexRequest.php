<?php

namespace App\Http\Requests;

use App\Enums\EventStatus;
use App\Models\Organizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizerEventIndexRequest extends FormRequest
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

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(EventStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
