<?php

use App\Support\BreakingMigration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4, step 2: utf8mb3 -> utf8mb4 (so emoji and every Unicode character can be stored, and so all tables
 * share one character set). Text is converted losslessly; row counts are verified.
 */
return new class extends BreakingMigration
{
    private const TABLES = [
        'users', 'user_groups', 'user_statuses', 'contents', 'categories', 'banners', 'banner_categories',
        'documents', 'document_categories', 'menus', 'customers', 'countries', 'states', 'cities', 'districts', 'thanas',
        'acl_rules', 'acl_controllers', 'acl_actions', 'audit_trails', 'visitors',
    ];

    public function up(): void
    {
        $this->ensureYiiRetired();
        $before = $this->rowCounts(self::TABLES);

        $this->withRelaxedSqlMode(function () {
            foreach (self::TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                $current = DB::selectOne('select table_collation as c from information_schema.tables where table_schema = database() and table_name = ?', [$this->t($table)])->c;
                if (str_starts_with((string) $current, 'utf8mb4')) {
                    continue;   // already converted (safe to re-run)
                }
                $this->sql('ALTER TABLE `'.$this->t($table).'` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            }

            // The banner-category alias was case-sensitive (binary collation); keep that.
            if (Schema::hasTable('banner_categories')) {
                $this->sql('ALTER TABLE `'.$this->t('banner_categories').'` MODIFY `alias` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL');
            }
        });

        $this->assertSameRowCounts($before);
    }

    public function down(): void
    {
        // Not reversible on purpose: restore the pre-deploy backup instead.
    }
};
