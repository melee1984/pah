<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiderVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'make_model' => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:100'],
        ];
    }
}
