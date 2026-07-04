<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavedEventIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->volunteerProfile()->exists() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
