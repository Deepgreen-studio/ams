<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Services\CmsSeoService;
use App\Domains\Content\Services\HeadlessContentService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CmsSeoController
{
    public function __construct(
        private readonly HeadlessContentService $headlessContentService,
        private readonly CmsSeoService $cmsSeoService
    ) {}

    public function sitemap(): Response
    {
        $entries = $this->headlessContentService->sitemapEntries();
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($entries as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.e($entry['loc']).'</loc>';
            if (! empty($entry['lastmod'])) {
                $lines[] = '    <lastmod>'.e($entry['lastmod']).'</lastmod>';
            }
            if (! empty($entry['changefreq'])) {
                $lines[] = '    <changefreq>'.e($entry['changefreq']).'</changefreq>';
            }
            if (! empty($entry['priority'])) {
                $lines[] = '    <priority>'.e($entry['priority']).'</priority>';
            }
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    public function robots(): Response
    {
        return response($this->cmsSeoService->robotsTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemapJson(): JsonResponse
    {
        $entries = $this->headlessContentService->sitemapEntries();

        return ApiResponse::success([
            'entries' => $entries,
            'count' => count($entries),
        ]);
    }

    public function robotsJson(): JsonResponse
    {
        return ApiResponse::success([
            'robots' => $this->cmsSeoService->robotsTxt(),
            'sitemap_url' => config('cms.robots.sitemap_url') ?: url('/sitemap.xml'),
        ]);
    }
}
