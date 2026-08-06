<?php

namespace App\Domains\Content\Requests;

use App\Domains\Content\Enums\ContentBodyFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AutosaveContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'excerpt' => ['nullable', 'string', 'max:5000'],
            'body' => ['nullable', 'string'],
            'body_format' => ['nullable', Rule::in(ContentBodyFormat::values())],
            'editor_json' => ['nullable', 'array'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:512'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:512'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'twitter_card' => ['nullable', 'string', 'in:summary,summary_large_image'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:512'],
            'twitter_image' => ['nullable', 'string', 'max:500'],
            'schema_type' => ['nullable', 'string', 'max:100'],
            'schema_json' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
