<?php

namespace App\Http\Requests;

use App\Enums\EventMode;
use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('q')) {
            $data['q'] = trim((string) $this->query('q'));
        }

        if (in_array($this->query('featured'), ['true', 'false'], true)) {
            $data['featured'] = $this->query('featured') === 'true';
        }

        $this->merge($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'categoryId' => ['nullable', 'string', 'exists:categories,id'],
            'mode' => ['nullable', Rule::enum(EventMode::class)],
            'status' => ['nullable', Rule::enum(EventStatus::class)],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['relevance', 'latest', 'eventDate', 'remainingQuota'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
