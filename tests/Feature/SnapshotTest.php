<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\BannerCategory;
use App\Models\Category;
use App\Models\Content;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Guards "the public design must not change". Renders key public pages from fixed data and compares
 * the HTML with tests/snapshots/*.html. After an INTENTIONAL markup change, regenerate with:
 *   UPDATE_SNAPSHOTS=1 php artisan test --filter=SnapshotTest
 * and review the diff in git before committing.
 */
class SnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function seedFixedData(): void
    {
        $cat = Category::factory()->create(['id' => 6, 'title' => 'Police', 'alias' => 'Police']);
        Category::factory()->create(['id' => 7, 'parent_id' => 6, 'title' => 'Sub', 'alias' => 'Police/Sub']);
        Category::factory()->create(['id' => 3, 'title' => 'Recent', 'alias' => 'Recent']);
        BannerCategory::factory()->create(['id' => 5, 'title' => 'Header banners']);
        BannerCategory::factory()->create(['id' => 2, 'title' => 'Gallery']);
        DocumentCategory::factory()->create(['id' => 1, 'title' => 'Forms']);
        Content::factory()->create(['id' => 2, 'category_id' => 6, 'title' => 'Message', 'details' => '<p>Body</p>', 'published_at' => '2025-01-02 03:04:05']);
        Content::factory()->create(['id' => 3, 'category_id' => 3, 'title' => 'Recent one', 'details' => '<p>Hello world</p>', 'published_at' => '2025-02-03 04:05:06']);
        Banner::factory()->create(['id' => 20, 'category_id' => 5, 'title' => 'Header']);
        Banner::factory()->create(['id' => 1, 'category_id' => 2, 'title' => 'Gallery photo', 'published_at' => '2025-03-03 00:00:00']);
        Document::factory()->create(['id' => 1, 'category_id' => 1, 'title' => 'A form', 'published_at' => '2025-04-04 00:00:00']);
        $cat->touch();
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function pages(): array
    {
        return [
            'home' => ['/', 'home'],
            'content-view' => ['/content/2.html', 'content-view'],
            'content-list' => ['/content/index/6.html', 'content-list'],
            'document-list' => ['/document/index/1.html', 'document-list'],
            'gallery' => ['/gallery/index/2.html', 'gallery'],
            'contact' => ['/contact.html', 'contact'],
        ];
    }

    #[DataProvider('pages')]
    public function test_public_page_markup_is_unchanged(string $url, string $name): void
    {
        $this->seedFixedData();
        // The home slider and contact captcha are random by design; pin them for a stable snapshot.
        mt_srand(1);

        $html = $this->get($url)->assertOk()->getContent();
        $html = preg_replace('/name="_token" value="[^"]+"/', 'name="_token" value="TOKEN"', $html);
        $html = preg_replace('/id="csrf-token" value="[^"]+"/', 'id="csrf-token" value="TOKEN"', $html);
        $html = preg_replace('/what is \d \+ \d\?/', 'what is N + N?', $html);
        $html = preg_replace('/Copyright &copy; \d{4}/', 'Copyright &copy; YEAR', $html);
        $html = preg_replace('/\?v=\d+/', '?v=MTIME', $html); // cache-busting suffix differs per checkout
        $html = preg_replace('/>\s+</', ">\n<", trim($html));

        $file = base_path("tests/snapshots/{$name}.html");

        if (getenv('UPDATE_SNAPSHOTS') || ! is_file($file)) {
            file_put_contents($file, $html."\n");
            $this->markTestSkipped("Snapshot written: {$file}");
        }

        $this->assertSame(trim(file_get_contents($file)), $html, "Public markup changed for {$url}. If intended, rerun with UPDATE_SNAPSHOTS=1.");
    }
}
