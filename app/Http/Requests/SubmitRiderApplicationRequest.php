<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitRiderApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('rider_applications', 'email'),
                Rule::unique('users', 'email'),
            ],
            'mobile' => ['required', 'string', 'max:25', 'regex:/^\+?[0-9][0-9\s-]{6,24}$/'],
            'password' => ['required', 'string', 'min:7', 'max:72', 'confirmed'],
            'birth_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:'.now()->subYears(18)->format('Y-m-d'),
            ],
            'home_address' => ['required', 'string', 'max:1000'],
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_relationship' => ['required', 'string', 'max:100'],
            'emergency_contact_mobile' => ['required', 'string', 'max:25', 'regex:/^\+?[0-9][0-9\s-]{6,24}$/'],

            'government_id' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'drivers_license' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'vehicle_registration' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],

            'vehicle_type' => ['required', 'string', 'max:100'],
            'vehicle_make_model' => ['required', 'string', 'max:255'],
            'vehicle_plate_number' => ['required', 'string', 'max:50'],
            'vehicle_color' => ['required', 'string', 'max:100'],

            'payout_method' => ['required', 'string', 'max:100'],
            'payout_account_name' => ['required', 'string', 'max:255'],
            'payout_account_number' => ['required', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'mobile' => trim((string) $this->input('mobile')),
            'emergency_contact_mobile' => trim((string) $this->input('emergency_contact_mobile')),
        ]);
    }

    public function messages(): array
    {
        return [
            'birth_date.before_or_equal' => 'Rider applicants must be at least 18 years old.',
            'email.unique' => 'An account or rider application already uses this email address.',
        ];
    }
}
