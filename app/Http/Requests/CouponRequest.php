<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'coupon' => strtoupper(trim((string) $this->input('coupon'))),
            'partner_id' => $this->input('partner_id') ?: null,
            'discount_value' => $this->input('discount_value') === '' ? null : $this->input('discount_value'),
            'discount_percentage' => $this->input('discount_percentage') === '' ? null : $this->input('discount_percentage'),
            'condition' => $this->input('condition') === '' ? null : $this->input('condition'),
            'limit' => $this->input('limit') === '' ? null : $this->input('limit'),
        ]);
    }

    public function rules(): array
    {
        return [
            'coupon' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupon', 'coupon')->ignore($this->route('coupon')),
            ],
            'partner_id' => ['nullable', 'integer', Rule::exists('partners', 'id')->where('active', true)],
            'discount_value' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'condition' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => [
                'nullable',
                'date',
                Rule::when($this->filled('valid_from'), ['after_or_equal:valid_from']),
            ],
            'limit' => ['nullable', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('discount_value') === null && $this->input('discount_percentage') === null) {
                $validator->errors()->add('discount_value', 'Enter either a fixed discount value or a discount percentage.');
            }

            if ($this->input('discount_value') !== null && $this->input('discount_percentage') !== null) {
                $validator->errors()->add('discount_percentage', 'Enter either a fixed discount value or a discount percentage, not both.');
            }
        });
    }
}
