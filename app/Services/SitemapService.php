<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\Log;

class SitemapService
{
    public function generate(): bool
    {
        try {
            $articles = Article::where('status', 'published')
                ->orderBy('published_at', 'desc')
                ->get();

            $urls = $this->buildUrlEntries($articles);
            $xml = $this->wrapUrlset($urls);

            file_put_contents(public_path('sitemap.xml'), $xml);
            Log::info('SitemapService: sitemap.xml generated with ' . $articles->count() . ' published articles.');

            return true;
        } catch (\Exception $e) {
            Log::error('SitemapService: failed to generate sitemap - ' . $e->getMessage());
            return false;
        }
    }

    protected function wrapUrlset(string $urls): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . $urls
            . '</urlset>' . "\n";
    }

    protected function buildUrlEntries($articles): string
    {
        $entries = [];

        $entries[] = $this->buildUrlEntry(url('/'), 'daily', '1.0');
        $entries[] = $this->buildUrlEntry(url('/calendar'), 'hourly', '0.8');
        $entries[] = $this->buildUrlEntry(url('/categories'), 'weekly', '0.7');
        $entries[] = $this->buildUrlEntry(url('/market-analysis'), 'weekly', '0.7');
        $entries[] = $this->buildUrlEntry(url('/geopolitical'), 'weekly', '0.7');
        $entries[] = $this->buildUrlEntry(url('/about'), 'monthly', '0.5');
        $entries[] = $this->buildUrlEntry(url('/contact'), 'monthly', '0.4');

        foreach ($articles as $article) {
            $entries[] = $this->buildUrlEntry(
                $article->canonical_url ?: url('/articles/' . $article->slug),
                'weekly',
                '0.6',
                $article->updated_at->toAtomString()
            );
        }

        return implode("\n", $entries) . "\n";
    }

    protected function buildUrlEntry(string $loc, string $changefreq, string $priority, ?string $lastmod = null): string
    {
        $entry = '  <url>' . "\n";
        $entry .= '    <loc>' . e($loc) . '</loc>' . "\n";

        if ($lastmod) {
            $entry .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
        }

        $entry .= '    <changefreq>' . $changefreq . '</changefreq>' . "\n";
        $entry .= '    <priority>' . $priority . '</priority>' . "\n";
        $entry .= '  </url>' . "\n";

        return $entry;
    }
}
