<?php

namespace App\Http\Requests;

use App\Enums\EventMode;
use App\Models\Organizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organizer = $this->route('organizer');

        return $organizer instanceof Organizer
            && ($this->user()?->can('manage', $organizer) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'categoryId' => ['required', 'string', 'exists:categories,id'],
            'location' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'mode' => ['required', Rule::enum(EventMode::class)],
            'date' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['required', 'date_format:H:i', 'after:startTime'],
            'quota' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string'],
            'shortDescription' => ['nullable', 'string'],
            'image' => ['nullable', 'url'],
            'benefits' => ['present', 'array'],
            'benefits.*' => ['string', 'max:255'],
            'skills' => ['present', 'array'],
            'skills.*' => ['string', 'max:255'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'max:255', 'distinct'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:255', 'distinct'],
        ];
    }
}
