<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiderPersonalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'birth_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:'.now()->subYears(18)->format('Y-m-d'),
            ],
            'home_address' => ['required', 'string', 'max:1000'],
            'mobile' => ['required', 'string', 'max:25', 'regex:/^\+?[0-9][0-9\s-]{6,24}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.before_or_equal' => 'Rider applicants must be at least 18 years old.',
        ];
    }
}
