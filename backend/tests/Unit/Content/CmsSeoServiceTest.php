<?php

namespace Tests\Unit\Content;

use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentType;
use App\Domains\Content\Services\CmsSeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsSeoServiceTest extends TestCase
{
    use RefreshDatabase;

    private CmsSeoService $seoService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seoService = app(CmsSeoService::class);
        config([
            'cms.site_url' => 'https://cms.example.com',
            'cms.sitemap.path_pattern' => '/content/{type}/{slug}',
            'app.name' => 'AMS',
        ]);
    }

    public function test_meta_title_falls_back_to_content_title(): void
    {
        $content = new Content([
            'title' => 'About Us',
            'seo_title' => null,
        ]);

        $this->assertSame('About Us', $this->seoService->metaTitle($content));

        $content->seo_title = 'About | AMS';
        $this->assertSame('About | AMS', $this->seoService->metaTitle($content));
    }

    public function test_canonical_url_prefers_explicit_value(): void
    {
        $type = new ContentType(['slug' => 'page']);
        $content = new Content([
            'title' => 'Guide',
            'slug' => 'guide',
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'canonical_url' => 'https://custom.example/guide',
        ]);
        $content->setRelation('type', $type);

        $this->assertSame(
            'https://custom.example/guide',
            $this->seoService->canonicalUrl($content)
        );
    }

    public function test_canonical_url_builds_from_site_pattern(): void
    {
        $type = new ContentType(['slug' => 'blog']);
        $content = new Content([
            'title' => 'Post',
            'slug' => 'hello-world',
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'canonical_url' => null,
        ]);
        $content->setRelation('type', $type);

        $this->assertSame(
            'https://cms.example.com/content/blog/hello-world',
            $this->seoService->canonicalUrl($content)
        );
    }

    public function test_build_for_content_includes_og_twitter_and_schema(): void
    {
        $type = new ContentType(['slug' => 'page']);
        $content = new Content([
            'title' => 'Enterprise Guide',
            'slug' => 'enterprise-guide',
            'uuid' => '33333333-3333-3333-3333-333333333333',
            'seo_title' => 'Enterprise Guide SEO',
            'seo_description' => 'SEO description',
            'og_title' => 'OG Title',
            'twitter_card' => 'summary_large_image',
            'featured_image' => 'https://cdn.example.com/hero.jpg',
            'published_at' => now(),
        ]);
        $content->setRelation('type', $type);
        $content->setRelation('tags', collect());

        $seo = $this->seoService->buildForContent($content);

        $this->assertSame('Enterprise Guide SEO', $seo['title']);
        $this->assertSame('OG Title', $seo['open_graph']['title']);
        $this->assertSame('summary_large_image', $seo['twitter_card']['card']);
        $this->assertSame('https://schema.org', $seo['schema_org']['@context']);
        $this->assertSame('Article', $seo['schema_org']['@type']);
        $this->assertNotEmpty($seo['canonical_url']);
    }

    public function test_robots_txt_includes_sitemap_and_disallow_rules(): void
    {
        config([
            'cms.robots.allow' => ['/'],
            'cms.robots.disallow' => ['/admin', '/api'],
            'cms.robots.sitemap_url' => 'https://cms.example.com/sitemap.xml',
        ]);

        $text = $this->seoService->robotsTxt();

        $this->assertStringContainsString('User-agent: *', $text);
        $this->assertStringContainsString('Allow: /', $text);
        $this->assertStringContainsString('Disallow: /admin', $text);
        $this->assertStringContainsString('Sitemap: https://cms.example.com/sitemap.xml', $text);
    }
}
