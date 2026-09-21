<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:25', 'regex:/^\+?[0-9][0-9\s-]{6,24}$/'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('rider_applications', 'email'),
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'string', 'min:7', 'max:72'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'mobile' => trim((string) $this->input('mobile')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account or rider application already uses this email address.',
        ];
    }
}
