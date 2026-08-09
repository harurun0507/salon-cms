<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\News;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Throwable;

class SitemapXmlController extends Controller
{
    public function __invoke(): Response
    {
        $xml = $this->buildXml();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function buildXml(): string
    {
        $urls = [];

        try {
            $urls = $this->collectUrls();
        } catch (Throwable) {
            $urls = [];
        }

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.$this->escape($entry['loc']).'</loc>';
            if (! empty($entry['lastmod'])) {
                $lines[] = '    <lastmod>'.$this->escape($entry['lastmod']).'</lastmod>';
            }
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    /**
     * @return list<array{loc: string, lastmod: ?string}>
     */
    private function collectUrls(): array
    {
        $urls = [];

        foreach ($this->staticRouteNames() as $name) {
            if (! Route::has($name)) {
                continue;
            }

            $urls[] = [
                'loc' => route($name),
                'lastmod' => null,
            ];
        }

        foreach (News::published()->get(['slug', 'published_at', 'updated_at']) as $news) {
            $slug = trim((string) $news->slug);
            if ($slug === '') {
                continue;
            }

            $urls[] = [
                'loc' => route('news.show', ['slug' => $slug]),
                'lastmod' => $this->formatLastmod($news->updated_at ?? $news->published_at),
            ];
        }

        foreach (Blog::published()->get(['slug', 'published_at', 'updated_at']) as $blog) {
            $slug = trim((string) $blog->slug);
            if ($slug === '') {
                continue;
            }

            $urls[] = [
                'loc' => route('blog.show', ['slug' => $slug]),
                'lastmod' => $this->formatLastmod($blog->updated_at ?? $blog->published_at),
            ];
        }

        return $urls;
    }

    /**
     * @return list<string>
     */
    private function staticRouteNames(): array
    {
        return [
            'home',
            'campaign',
            'news.index',
            'blog.index',
            'menu',
            'gallery',
            'staff',
            'access',
            'privacy',
        ];
    }

    private function formatLastmod(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toAtomString();
        }

        return null;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
