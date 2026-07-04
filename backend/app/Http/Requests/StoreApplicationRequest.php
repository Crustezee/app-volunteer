<?php

namespace App\Http\Requests;

use App\Models\VolunteerEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreApplicationRequest extends FormRequest
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
            'role' => ['required', 'string', 'max:255'],
            'motivation' => ['required', 'string', 'min:24'],
            'availability' => ['required', 'array', 'min:1'],
            'availability.*' => ['string', 'max:255', 'distinct'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('role')) {
                    return;
                }

                $event = $this->route('event');

                if (
                    $event instanceof VolunteerEvent
                    && ! in_array($this->string('role')->toString(), $event->roles, true)
                ) {
                    $validator->errors()->add('role', 'Role tidak tersedia pada event ini.');
                }
            },
        ];
    }
}
