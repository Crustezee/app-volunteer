<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $role = $this->input('role', $this->input('accountType', 'volunteer'));

        $this->merge([
            'role' => $role,
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'password_confirmation' => $this->input('password_confirmation', $this->input('passwordConfirmation')),
            'name' => $this->input('name', $this->input('organizationName')),
            'organizationName' => $this->input('organizationName', $this->input('name')),
            'organizationType' => $this->input('organizationType', $this->input('type')),
            'interests' => $this->input('interests', $this->input('focus', [])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(['volunteer', 'organizer'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:32'],
            'city' => ['required', 'string', 'max:255'],
            'university' => [Rule::requiredIf($this->input('role') === 'volunteer'), 'nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string', 'max:255'],
            'organizationName' => [Rule::requiredIf($this->input('role') === 'organizer'), 'nullable', 'string', 'max:255'],
            'organizationType' => [Rule::requiredIf($this->input('role') === 'organizer'), 'nullable', 'string', 'max:255'],
        ];
    }
}
