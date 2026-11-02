<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Content;
use App\Models\Document;
use App\Support\SiteCache;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function robots()
    {
        $body = "User-agent: *\nDisallow: /admin\nDisallow: /feedback/\n\nSitemap: ".url('sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap()
    {
        $xml = Cache::remember(SiteCache::SITEMAP_KEY, SiteCache::TTL * 6, fn () => '<?xml version="1.0" encoding="UTF-8"?>'."\n".view('front.sitemap', ['urls' => $this->urls()])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** @return array<int, array{loc: string, lastmod: ?string, priority: string}> */
    private function urls(): array
    {
        $urls = [
            ['loc' => url('/'), 'lastmod' => null, 'priority' => '1.0'],
            ['loc' => route('front.contact'), 'lastmod' => null, 'priority' => '0.3'],
        ];

        $contents = Content::active()->orderBy('id')->get(['id', 'category_id', 'published_at']);

        // Listing pages (only categories that have published content).
        $lastByCategory = $contents->groupBy('category_id')->map(fn ($g) => $g->max('published_at'));
        foreach (Category::active()->whereIn('id', $lastByCategory->keys())->pluck('id') as $id) {
            $urls[] = ['loc' => route('front.content.index', $id), 'lastmod' => $this->date($lastByCategory[$id]), 'priority' => '0.6'];
        }

        foreach ($contents as $c) {
            $urls[] = ['loc' => route('front.content.view', $c->id), 'lastmod' => $this->date($c->published_at), 'priority' => '0.8'];
        }

        foreach (Document::active()->select('category_id')->selectRaw('max(published_at) as last')->groupBy('category_id')->toBase()->get() as $d) {
            $urls[] = ['loc' => route('front.document.index', $d->category_id), 'lastmod' => $this->date($d->last), 'priority' => '0.5'];
        }

        foreach (Banner::active()->select('category_id')->selectRaw('max(published_at) as last')->groupBy('category_id')->toBase()->get() as $b) {
            $urls[] = ['loc' => route('front.gallery.index', $b->category_id), 'lastmod' => $this->date($b->last), 'priority' => '0.4'];
        }

        return $urls;
    }

    private function date(mixed $value): ?string
    {
        if (empty($value) || str_starts_with((string) $value, '0000')) {
            return null;
        }

        return date('Y-m-d', strtotime((string) $value));
    }
}
