<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'slug' => Str::slug($this->input('slug') ?: $this->input('title')),
            'excerpt' => filled($this->input('excerpt')) ? trim((string) $this->input('excerpt')) : null,
            'meta_description' => filled($this->input('meta_description')) ? trim((string) $this->input('meta_description')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => [
                'required',
                'string',
                'max:190',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blog_posts', 'slug')->ignore($this->route('blogPost')),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:100000'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'action' => ['required', Rule::in(['save', 'draft', 'publish'])],
            'temporary_upload_ids' => ['nullable', 'array', 'max:20'],
            'temporary_upload_ids.*' => ['uuid', 'distinct'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer', 'distinct'],
        ];
    }
}
