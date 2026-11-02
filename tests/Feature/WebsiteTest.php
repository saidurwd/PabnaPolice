<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\BannerCategory;
use App\Models\Category;
use App\Models\Content;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Feedback;
use App\Models\Setting;
use App\View\Composers\FrontComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    private function seedContent(): Content
    {
        $cat = Category::factory()->create(['title' => 'Press', 'alias' => 'Press']);

        return Content::factory()->create([
            'title' => 'পাবনা পুলিশ', 'details' => '<p>Hello</p>', 'category_id' => $cat->id, 'published_at' => '2025-05-01 10:00:00',
        ]);
    }

    public function test_home_page_renders_even_on_an_empty_database(): void
    {
        $this->get('/')->assertOk()->assertSee('Quick Contact');
    }

    public function test_content_listing_and_page(): void
    {
        $c = $this->seedContent();
        Content::factory()->inactive()->create(['title' => 'Hidden one', 'category_id' => $c->category_id]);

        $this->get("/content/index/{$c->category_id}.html")->assertOk()->assertSee('পাবনা পুলিশ')->assertDontSee('Hidden one');
        $this->get("/content/{$c->id}.html")->assertOk()->assertSee('<p>Hello</p>', false);
        $this->get('/content/99999.html')->assertNotFound();
        $this->get("/content/view/{$c->id}.html")->assertRedirect("/content/{$c->id}.html");
    }

    public function test_menu_uses_the_configured_categories(): void
    {
        $c = $this->seedContent();
        Setting::put('nav_categories', (string) $c->category_id);

        $this->get('/contact.html')->assertOk()->assertSee('Press')->assertSee("/content/{$c->id}.html", false);
    }

    public function test_gallery_and_document_download(): void
    {
        Banner::factory()->create(['category_id' => BannerCategory::factory()->create(['id' => 4])->id, 'title' => 'Wanted']);
        $this->get('/gallery/index/4.html')->assertOk()->assertSee('Wanted');

        $doc = Document::factory()->create(['category_id' => DocumentCategory::factory()->create(['id' => 1])->id, 'title' => 'Form']);
        $this->get("/document/download/{$doc->id}.html")->assertNotFound();
        $this->get('/document/index/1.html')->assertOk()->assertSee('Form');
    }

    public function test_quick_contact_and_contact_form_store_feedback(): void
    {
        $this->post('/feedback/post.html', ['name' => 'Rahim', 'email' => 'r@example.com', 'message' => 'Hi'])
            ->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('feedback', ['name' => 'Rahim']);

        $this->get('/contact.html');
        $answer = session('contact_captcha');
        $this->post('/contact.html', ['name' => 'Karim', 'email' => 'k@example.com', 'subject' => 'S', 'body' => 'B', 'captcha' => $answer])
            ->assertRedirect('/contact.html');
        $this->assertSame(2, Feedback::count());

        $this->post('/contact.html', ['name' => 'Bot', 'email' => 'b@example.com', 'subject' => 'S', 'body' => 'B', 'captcha' => 999])
            ->assertSessionHasErrors('captcha');
    }

    public function test_robots_and_sitemap(): void
    {
        $c = $this->seedContent();
        Content::factory()->inactive()->create(['title' => 'Hidden', 'category_id' => $c->category_id]);

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('sitemap.xml');

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertStringContainsString("/content/{$c->id}.html", $xml);
        $this->assertStringContainsString("/content/index/{$c->category_id}.html", $xml);
        $this->assertStringContainsString('<lastmod>2025-05-01</lastmod>', $xml);
        $this->assertStringNotContainsString('/content/'.($c->id + 1).'.html', $xml, 'inactive content must not be listed');
        $this->assertNotFalse(simplexml_load_string($xml), 'sitemap must be well-formed XML');
    }

    public function test_pages_have_seo_tags(): void
    {
        $c = $this->seedContent();
        $c->update(['metadesc' => 'Custom description']);

        $this->get("/content/{$c->id}.html")
            ->assertSee('<link rel="canonical" href="'.url("/content/{$c->id}.html").'">', false)
            ->assertSee('<meta name="description" content="Custom description">', false)
            ->assertSee('<meta property="og:type" content="article">', false);
        $this->get('/')->assertSee('application/ld+json', false)->assertSee('GovernmentOrganization', false);
        $this->get('/admin/login')->assertSee('noindex', false);
    }

    /** Regression: the shared menu data must survive a real serialize/unserialize (the file cache). */
    public function test_shared_layout_data_works_from_the_file_cache(): void
    {
        config(['cache.default' => 'file', 'cache.stores.file.path' => storage_path('framework/testing/cache')]);
        Cache::flush();
        $c = $this->seedContent();
        Setting::put('nav_categories', (string) $c->category_id);

        $this->get('/contact.html')->assertOk()->assertSee('Press');   // warms the cache
        $this->app->forgetInstance(FrontComposer::class);
        $this->get('/contact.html')->assertOk()->assertSee('Press');   // served from the cache

        Cache::flush();
    }
}
