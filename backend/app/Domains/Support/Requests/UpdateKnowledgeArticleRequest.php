<?php

namespace App\Domains\Support\Requests;

use App\Domains\Support\Enums\KnowledgeArticleStatus;
use App\Domains\Support\Enums\KnowledgeArticleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKnowledgeArticleRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(KnowledgeArticleType::values())],
            'status' => ['nullable', Rule::in(KnowledgeArticleStatus::values())],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'body_format' => ['nullable', Rule::in(['html', 'markdown', 'plain'])],
            'video_url' => ['nullable', 'url', 'max:1000'],
            'featured_image' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'string'],
            'content_id' => ['nullable', 'string'],
            'sync_from_cms' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'related_article_ids' => ['nullable', 'array'],
            'related_article_ids.*' => ['string'],
            'version_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
