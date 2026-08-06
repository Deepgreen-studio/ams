<?php

namespace App\Domains\Content\Requests;

use App\Domains\Content\Enums\ContentBodyFormat;
use App\Domains\Content\Enums\ContentStatusSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_type_id' => ['required', 'string'],
            'content_category_id' => ['nullable', 'string'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
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
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'status' => ['nullable', Rule::in(ContentStatusSlug::values())],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
