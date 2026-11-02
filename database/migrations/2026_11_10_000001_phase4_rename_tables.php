<?php

use App\Support\BreakingMigration;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4, step 1: Laravel-conventional (plural) table names. Data is untouched; RENAME TABLE is atomic.
 * The `os_` prefix stays (a supported Laravel feature): dropping it would also mean renaming the migrations table
 * while it is being used.
 */
return new class extends BreakingMigration
{
    public const RENAMES = [
        'user' => 'users', 'user_group' => 'user_groups', 'user_status' => 'user_statuses',
        'content' => 'contents', 'category' => 'categories',
        'banner' => 'banners', 'banner_category' => 'banner_categories',
        'document' => 'documents', 'document_category' => 'document_categories',
        'menu' => 'menus', 'customer' => 'customers',
        'country' => 'countries', 'state' => 'states', 'city' => 'cities', 'district' => 'districts', 'thana' => 'thanas',
        'acl' => 'acl_rules', 'acl_controller' => 'acl_controllers', 'acl_action' => 'acl_actions',
        'audit_trail' => 'audit_trails', 'visitor' => 'visitors',
        'setting' => 'settings', 'site_link' => 'site_links',
    ];

    public function up(): void
    {
        $this->ensureYiiRetired();
        $before = $this->rowCounts(array_keys(self::RENAMES));

        foreach (self::RENAMES as $old => $new) {
            if (Schema::hasTable($old) && ! Schema::hasTable($new)) {
                Schema::rename($old, $new);
            }
        }

        $this->assertSameRowCounts($before, self::RENAMES);
    }

    public function down(): void
    {
        // Not reversible on purpose: restore the pre-deploy backup instead.
    }
};
