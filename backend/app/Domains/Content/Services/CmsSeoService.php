<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Models\Content;
use Illuminate\Support\Str;

class CmsSeoService
{
    /**
     * @return array{
     *   title: string,
     *   description: string|null,
     *   keywords: string|null,
     *   canonical_url: string,
     *   robots: string,
     *   open_graph: array<string, mixed>,
     *   twitter_card: array<string, mixed>,
     *   schema_org: array<string, mixed>
     * }
     */
    public function buildForContent(Content $content): array
    {
        $title = $this->metaTitle($content);
        $description = $this->metaDescription($content);
        $canonical = $this->canonicalUrl($content);
        $image = $this->socialImage($content);
        $keywords = $content->seo_keywords;

        $openGraph = [
            'type' => 'article',
            'title' => $content->og_title ?: $title,
            'description' => $content->og_description ?: $description,
            'url' => $canonical,
            'image' => $content->og_image ?: $image,
            'site_name' => config('app.name'),
            'locale' => str_replace('_', '-', app()->getLocale()),
        ];

        if ($content->published_at) {
            $openGraph['article:published_time'] = $content->published_at->toIso8601String();
        }

        if ($content->updated_at) {
            $openGraph['article:modified_time'] = $content->updated_at->toIso8601String();
        }

        $twitter = [
            'card' => $content->twitter_card ?: 'summary_large_image',
            'title' => $content->twitter_title ?: ($content->og_title ?: $title),
            'description' => $content->twitter_description ?: ($content->og_description ?: $description),
            'image' => $content->twitter_image ?: ($content->og_image ?: $image),
        ];

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'canonical_url' => $canonical,
            'robots' => 'index,follow',
            'open_graph' => $openGraph,
            'twitter_card' => $twitter,
            'schema_org' => $this->schemaOrg($content, $title, $description, $canonical, $image),
        ];
    }

    public function metaTitle(Content $content): string
    {
        return (string) ($content->seo_title ?: $content->title);
    }

    public function metaDescription(Content $content): ?string
    {
        $value = $content->seo_description ?: $content->excerpt ?: $content->summary;

        if ($value === null || $value === '') {
            return null;
        }

        return Str::limit(strip_tags((string) $value), 320, '');
    }

    public function canonicalUrl(Content $content): string
    {
        if (filled($content->canonical_url)) {
            return (string) $content->canonical_url;
        }

        return $this->publicContentUrl($content);
    }

    public function publicContentUrl(Content $content): string
    {
        $pattern = (string) config('cms.sitemap.path_pattern', '/content/{type}/{slug}');
        $path = str_replace(
            ['{type}', '{slug}', '{uuid}'],
            [
                $content->type?->slug ?: 'content',
                $content->slug,
                $content->uuid,
            ],
            $pattern
        );

        return rtrim((string) config('cms.site_url'), '/').'/'.ltrim($path, '/');
    }

    public function socialImage(Content $content): ?string
    {
        $image = $content->og_image ?: $content->twitter_image ?: $content->featured_image;

        if (! $image) {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($image, '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function schemaOrg(
        Content $content,
        ?string $title = null,
        ?string $description = null,
        ?string $canonical = null,
        ?string $image = null
    ): array {
        if (is_array($content->schema_json) && $content->schema_json !== []) {
            return $content->schema_json;
        }

        $type = $content->schema_type ?: 'Article';
        $title ??= $this->metaTitle($content);
        $description ??= $this->metaDescription($content);
        $canonical ??= $this->canonicalUrl($content);
        $image ??= $this->socialImage($content);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'headline' => $title,
            'name' => $content->title,
            'description' => $description,
            'url' => $canonical,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonical,
            ],
            'datePublished' => $content->published_at?->toIso8601String(),
            'dateModified' => $content->updated_at?->toIso8601String(),
        ];

        if ($image) {
            $schema['image'] = [$image];
        }

        if ($content->relationLoaded('creator') && $content->creator) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $content->creator->full_name ?: $content->creator->email,
            ];
        }

        if ($content->relationLoaded('tags') && $content->tags->isNotEmpty()) {
            $schema['keywords'] = $content->tags->pluck('name')->implode(', ');
        }

        return array_filter(
            $schema,
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    public function robotsTxt(): string
    {
        $lines = [
            'User-agent: *',
        ];

        foreach ((array) config('cms.robots.allow', ['/']) as $allow) {
            $lines[] = 'Allow: '.$allow;
        }

        foreach ((array) config('cms.robots.disallow', []) as $disallow) {
            $lines[] = 'Disallow: '.$disallow;
        }

        $sitemap = config('cms.robots.sitemap_url') ?: url('/sitemap.xml');
        $lines[] = '';
        $lines[] = 'Sitemap: '.$sitemap;

        return implode("\n", $lines)."\n";
    }
}
