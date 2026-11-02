<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-organises the admin sidebar into clear sections. Only the `menus` rows (navigation) change; no content moves.
 * Existing rows are re-used (matched by their old URL or title) so permissions keep working; missing ones are created.
 * Safe to run twice.
 */
return new class extends Migration
{
    /** key => [title, icon, section heading, ordering, old titles to adopt] */
    private const PARENTS = [
        'content' => ['Content', 'bi-file-earmark-text', 'Content', 10, ['Content Management']],
        'gallery' => ['Gallery & banners', 'bi-images', 'Content', 20, ['Banner Management']],
        'documents' => ['Documents', 'bi-folder', 'Content', 30, ['Document Management']],
        'website' => ['Website', 'bi-globe2', 'Website', 50, []],
        'people' => ['Users & access', 'bi-people', 'Administration', 60, ['Access Control']],
        'places' => ['Reference data', 'bi-geo-alt', 'Administration', 70, []],
        'advanced' => ['Advanced', 'bi-gear', 'Administration', 80, ['Configuration']],
    ];

    /** Top-level links: key => [title, url, acl key, icon, section, ordering, old urls] */
    private const LINKS = [
        'dashboard' => ['Dashboard', '/dashboard', null, 'bi-speedometer2', null, 1, ['/site/index']],
        'feedback' => ['Feedback', '/feedback', 'feedback', 'bi-chat-dots', 'Content', 40, ['/feedback/admin']],
    ];

    /** [parent key, title, url, acl key, ordering, old urls] */
    private const ITEMS = [
        ['content', 'Content', '/content', 'content', 1, ['/content/admin']],
        ['content', 'Categories', '/category', 'category', 2, ['/category/admin']],
        ['gallery', 'Banners', '/banner', 'banner', 1, ['/banner/admin']],
        ['gallery', 'Banner categories', '/banner-category', 'bannerCategory', 2, ['/bannerCategory/admin']],
        ['documents', 'Documents', '/document', 'document', 1, ['/document/admin']],
        ['documents', 'Document categories', '/document-category', 'documentCategory', 2, ['/documentCategory/admin']],
        ['website', 'Site settings', '/setting', 'setting', 1, ['/setting/admin']],
        ['website', 'Site links', '/site-link', 'siteLink', 2, ['/siteLink/admin']],
        ['people', 'Users', '/user', 'user', 1, ['/user/admin']],
        ['people', 'Roles & permissions', '/role', 'role', 2, ['/userGroup/admin', '/user-group']],
        ['people', 'Sign-in history', '/audit-trail', 'auditTrail', 3, ['/auditTrail/admin']],
        ['places', 'Countries', '/country', 'Country', 1, ['/country/admin']],
        ['places', 'States', '/state', 'State', 2, ['/state/admin']],
        ['places', 'Cities', '/city', 'City', 3, ['/city/admin']],
        ['places', 'Districts', '/district', 'District', 4, ['/district/admin']],
        ['places', 'Thanas', '/thana', 'Thana', 5, ['/thana/admin']],
        ['advanced', 'Admin menu', '/menu', 'menu', 1, ['/menu/admin']],
        ['advanced', 'User statuses', '/user-status', 'userStatus', 2, ['/userStatus/admin']],
    ];

    public function up(): void
    {
        $parentIds = [];

        foreach (self::PARENTS as $key => [$title, $icon, $section, $ordering, $adopt]) {
            $row = DB::table('menus')->whereNull('parent_id')->where('url', '#')->whereIn('title', array_merge([$title], $adopt))->orderBy('id')->first();
            $values = ['title' => $title, 'icon' => $icon, 'group' => $section, 'ordering' => $ordering, 'status' => 1, 'controller' => null, 'url' => '#', 'parent_id' => null];
            $parentIds[$key] = $row ? tap($row->id, fn ($id) => DB::table('menus')->where('id', $id)->update($values)) : DB::table('menus')->insertGetId($values);
        }

        foreach (self::LINKS as [$title, $url, $acl, $icon, $section, $ordering, $old]) {
            $this->upsert(array_merge([$url], $old), ['title' => $title, 'url' => $url, 'controller' => $acl, 'icon' => $icon, 'group' => $section, 'ordering' => $ordering, 'status' => 1, 'parent_id' => null]);
        }

        foreach (self::ITEMS as [$parentKey, $title, $url, $acl, $ordering, $old]) {
            $this->upsert(array_merge([$url], $old), ['parent_id' => $parentIds[$parentKey], 'title' => $title, 'url' => $url, 'controller' => $acl, 'icon' => null, 'group' => null, 'ordering' => $ordering, 'status' => 1]);
        }

        // Screens that no longer exist (the old group/permission editors).
        DB::table('menus')->whereIn('url', ['/aclController/admin', '/acl-controller', '/acl-action', '/aclAction/admin'])->delete();

        // Old heading rows that nothing is left under (e.g. "Customer Management") are no longer needed.
        $used = array_merge(array_values($parentIds), DB::table('menus')->whereNotNull('parent_id')->pluck('parent_id')->all());
        DB::table('menus')->whereNull('parent_id')->where('url', '#')->whereNotIn('id', $used)->delete();
    }

    /** Updates the first row with one of these URLs (creating it if none) and removes any other duplicates. */
    private function upsert(array $urls, array $values): void
    {
        $ids = DB::table('menus')->whereIn('url', $urls)->orderBy('id')->pluck('id')->all();

        if ($ids === []) {
            DB::table('menus')->insert($values);

            return;
        }

        DB::table('menus')->where('id', $ids[0])->update($values);
        if (count($ids) > 1) {
            DB::table('menus')->whereIn('id', array_slice($ids, 1))->delete();
        }
    }

    public function down(): void
    {
        // Navigation only; nothing to undo safely.
    }
};
