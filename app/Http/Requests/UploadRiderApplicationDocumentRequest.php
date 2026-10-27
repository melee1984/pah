<?php

namespace App\Http\Requests;

use App\RiderApplicationDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadRiderApplicationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(RiderApplicationDocument::TYPES)],
            'file' => [
                'required',
                'file',
                Rule::when(
                    $this->input('type') === 'profile_photo',
                    ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                    ['mimes:jpg,jpeg,png,pdf', 'max:10240'],
                ),
            ],
        ];
    }
}
