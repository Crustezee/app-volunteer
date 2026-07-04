<?php

namespace App\Http\Requests;

use App\Enums\EventMode;
use App\Enums\EventStatus;
use App\Models\VolunteerEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof VolunteerEvent
            && ($this->user()?->can('update', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'categoryId' => ['sometimes', 'string', 'exists:categories,id'],
            'location' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'mode' => ['sometimes', Rule::enum(EventMode::class)],
            'date' => ['sometimes', 'date'],
            'startTime' => ['sometimes', 'date_format:H:i'],
            'endTime' => ['sometimes', 'date_format:H:i'],
            'quota' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::enum(EventStatus::class)],
            'description' => ['sometimes', 'string'],
            'shortDescription' => ['sometimes', 'string'],
            'image' => ['sometimes', 'url'],
            'benefits' => ['sometimes', 'array'],
            'benefits.*' => ['string', 'max:255'],
            'skills' => ['sometimes', 'array'],
            'skills.*' => ['string', 'max:255'],
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['string', 'max:255', 'distinct'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:255', 'distinct'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var VolunteerEvent $event */
                $event = $this->route('event');
                $editable = array_keys($this->rules());

                if (! collect($editable)->contains(fn (string $field) => $this->exists($field))) {
                    $validator->errors()->add('event', 'Minimal satu field harus diubah.');
                }

                $startTime = (string) $this->input('startTime', $event->start_time);
                $endTime = (string) $this->input('endTime', $event->end_time);

                if ($startTime >= $endTime) {
                    $validator->errors()->add('endTime', 'Waktu selesai harus setelah waktu mulai.');
                }

                if ($this->filled('quota') && $this->integer('quota') < $event->registered) {
                    $validator->errors()->add(
                        'quota',
                        'Kuota tidak boleh lebih kecil dari jumlah pendaftar.'
                    );
                }
            },
        ];
    }
}
