<?php

namespace App\View\Composers;

use App\Enums\YesNo;
use App\Models\Banner;
use App\Models\BannerCategory;
use App\Models\Category;
use App\Models\Content;
use App\Models\DocumentCategory;
use App\Models\SiteLink;
use App\Support\SiteCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/** Supplies the layout (main menu, side panels, footer) with data that the Yii theme queried inline. */
class FrontComposer
{
    private ?array $data = null;

    public function compose(View $view): void
    {
        // Registered for every front view; computed once per request (the composer is a scoped binding).
        $this->data ??= Cache::remember(SiteCache::SHARED_KEY, SiteCache::TTL, fn () => [
            'nav' => $this->nav(),
            'navGallery' => $this->gallery(),
            'navDocument' => DocumentCategory::find((int) setting('nav_document_category')),
            'headerBanner' => Banner::find((int) setting('header_banner_id')),
            'links' => SiteLink::active()->orderBy('ordering')->orderBy('id')->get()->groupBy('group'),
        ]);

        $view->with($this->data);
    }

    /** @return array<int, array{category: Category, contents: Collection, children: array}> */
    private function nav(): array
    {
        $ids = $this->ids(setting('nav_categories'));
        $listingIds = $this->ids(setting('nav_listing_categories'));
        if (! $ids) {
            return [];
        }

        $categories = Category::active()->whereIn('id', $ids)->get()->keyBy('id');
        $children = Category::active()->whereIn('parent_id', $ids)->orderBy('id')->get()->groupBy('parent_id');

        $contentCats = collect($ids)->merge($children->flatten()->pluck('id'))->unique()->all();
        $contents = Content::active()->whereIn('category_id', $contentCats)->orderBy('id')->get(['id', 'title', 'category_id'])->groupBy('category_id');

        $menu = [];
        foreach ($ids as $id) {
            if (! $cat = $categories->get($id)) {
                continue;
            }
            $kids = $children->get($id, collect());
            $listing = in_array($id, $listingIds, true);

            $menu[] = [
                'category' => $cat,
                'contents' => $contents->get($id, collect()),
                // Sub-categories flagged "listing" expand to their contents (only for configured menus).
                'expanded' => $listing ? $kids->filter(fn ($k) => $k->listing === YesNo::Yes)->map(fn ($k) => ['category' => $k, 'contents' => $contents->get($k->id, collect())])->values()->all() : [],
                'children' => $listing ? $kids->reject(fn ($k) => $k->listing === YesNo::Yes)->values()->all() : $kids->values()->all(),
            ];
        }

        return $menu;
    }

    private function gallery(): ?array
    {
        $cat = BannerCategory::find((int) setting('nav_gallery_category'));

        return $cat ? ['category' => $cat, 'items' => BannerCategory::active()->where('parent_id', $cat->id)->get()] : null;
    }

    private function ids(?string $csv): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) $csv))));
    }
}
