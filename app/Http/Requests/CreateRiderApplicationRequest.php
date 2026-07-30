<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRiderApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('rider_applications', 'email'),
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
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
