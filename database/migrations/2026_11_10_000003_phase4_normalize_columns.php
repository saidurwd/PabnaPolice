<?php

use App\Support\BreakingMigration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4, step 3: Laravel column conventions.
 *
 *  - foreign-key-like columns get an `_id` suffix (category -> category_id, parent -> parent_id, user.status -> status_id ...)
 *  - `parent = 0` (meaning "top level") becomes NULL
 *  - `created_on` becomes `published_at` on content/banners/documents, and `created_at`/`updated_at` are added
 *    (back-filled from the old date). The old `ON UPDATE CURRENT_TIMESTAMP` that silently moved dates is gone.
 *  - user/customer `register_date`/`lastvisit` become `created_at`/`last_login_at`; '0000-00-00' becomes NULL
 *  - ENUM('Active','Inactive') / ENUM('Yes','No') columns become VARCHAR (values unchanged; the app casts them to enums)
 */
return new class extends BreakingMigration
{
    /** table => [old column => new column] (type kept; adjusted below where needed) */
    private const ID_RENAMES = [
        'contents' => ['category' => 'category_id'],
        'banners' => ['category' => 'category_id'],
        'documents' => ['category' => 'category_id'],
        'categories' => ['parent' => 'parent_id'],
        'banner_categories' => ['parent' => 'parent_id'],
        'document_categories' => ['parent' => 'parent_id'],
        'menus' => ['parent' => 'parent_id'],
        'states' => ['country' => 'country_id'],
        'cities' => ['country' => 'country_id', 'state' => 'state_id'],
        'districts' => ['country' => 'country_id', 'state' => 'state_id', 'city' => 'city_id'],
        'thanas' => ['country' => 'country_id', 'state' => 'state_id', 'city' => 'city_id', 'district' => 'district_id'],
        'users' => ['status' => 'status_id'],
        'customers' => ['group' => 'group_id', 'status' => 'status_id'],
    ];

    /** table => [column, VARCHAR length, default] */
    private const ENUMS = [
        'contents' => [['status', 20, 'Active'], ['featured', 3, 'No']],
        'categories' => [['status', 20, 'Active'], ['listing', 3, 'No']],
        'banner_categories' => [['status', 20, 'Active']],
        'document_categories' => [['status', 20, 'Active']],
        'banners' => [['status', 20, 'Active'], ['sticky', 3, 'No']],
        'documents' => [['status', 20, 'Active']],
        'countries' => [['status', 20, 'Active']], 'states' => [['status', 20, 'Active']], 'cities' => [['status', 20, 'Active']],
        'districts' => [['status', 20, 'Active']], 'thanas' => [['status', 20, 'Active']],
        'site_links' => [['status', 20, 'Active']],
        'user_groups' => [['group_type', 10, 'Front']],
    ];

    public function up(): void
    {
        $this->ensureYiiRetired();

        $tables = array_unique(array_merge(array_keys(self::ID_RENAMES), array_keys(self::ENUMS), ['users', 'customers']));
        $before = $this->rowCounts($tables);

        $this->withRelaxedSqlMode(function () {
            $this->topLevelZeroToNull();
            $this->renameIdColumns();
            $this->convertDates();
            $this->convertEnums();
        });

        $this->assertSameRowCounts($before);
    }

    /** parent = 0 means "top level" in the legacy data; NULL is the real thing (and lets us add a foreign key). */
    private function topLevelZeroToNull(): void
    {
        foreach (['categories', 'banner_categories', 'document_categories', 'menus'] as $table) {
            if (Schema::hasColumn($table, 'parent')) {
                $this->sql('ALTER TABLE `'.$this->t($table).'` MODIFY `parent` '.($table === 'menus' ? 'INT UNSIGNED' : 'INT').' NULL DEFAULT NULL');
                DB::table($table)->where('parent', 0)->update(['parent' => null]);
            }
        }
    }

    private function renameIdColumns(): void
    {
        foreach (self::ID_RENAMES as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $old => $new) {
                if (! Schema::hasColumn($table, $old) || Schema::hasColumn($table, $new)) {
                    continue;
                }
                $type = $this->columnType($table, $old);
                // The NOT NULL / DEFAULT of the old column is kept, except the old `DEFAULT 0` on user/customer status.
                $null = $this->isNullable($table, $old) ? 'NULL' : 'NOT NULL';
                $default = DB::selectOne('select column_default as d from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?', [$this->t($table), $old])->d;
                // MariaDB reports "no default" as the text 'NULL'; MySQL as a real NULL.
                $hasDefault = $default !== null && strtoupper((string) $default) !== 'NULL';
                $defaultSql = $hasDefault ? 'DEFAULT '.DB::getPdo()->quote((string) $default) : ($null === 'NULL' ? 'DEFAULT NULL' : '');
                $this->sql("ALTER TABLE `{$this->t($table)}` CHANGE `{$old}` `{$new}` {$type} {$null} {$defaultSql}");
            }
        }
    }

    private function convertDates(): void
    {
        // users / customers: register_date -> created_at, lastvisit -> last_login_at, zero dates -> NULL, updated_at added.
        foreach (['users', 'customers'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (['register_date' => 'created_at', 'lastvisit' => 'last_login_at'] as $old => $new) {
                if (Schema::hasColumn($table, $old) && ! Schema::hasColumn($table, $new)) {
                    $this->sql("ALTER TABLE `{$this->t($table)}` MODIFY `{$old}` TIMESTAMP NULL DEFAULT NULL");
                    DB::table($table)->whereRaw("CAST(`{$old}` AS CHAR) LIKE '0000%'")->update([$old => null]);
                    $this->sql("ALTER TABLE `{$this->t($table)}` CHANGE `{$old}` `{$new}` TIMESTAMP NULL DEFAULT NULL");
                }
            }
            $this->addUpdatedAt($table);
        }

        // content / banners / documents: created_on -> published_at (+ created_at, updated_at back-filled from it).
        foreach (['contents', 'banners', 'documents'] as $table) {
            if (Schema::hasColumn($table, 'created_on') && ! Schema::hasColumn($table, 'published_at')) {
                $this->sql("ALTER TABLE `{$this->t($table)}` CHANGE `created_on` `published_at` TIMESTAMP NULL DEFAULT NULL");
                DB::table($table)->whereRaw("CAST(`published_at` AS CHAR) LIKE '0000%'")->update(['published_at' => null]);
                $this->addCreatedAndUpdatedAt($table, 'published_at');
            }
        }

        // categories: created_on is just a creation time.
        foreach (['categories', 'banner_categories', 'document_categories'] as $table) {
            if (Schema::hasColumn($table, 'created_on') && ! Schema::hasColumn($table, 'created_at')) {
                $this->sql("ALTER TABLE `{$this->t($table)}` CHANGE `created_on` `created_at` TIMESTAMP NULL DEFAULT NULL");
                DB::table($table)->whereRaw("CAST(`created_at` AS CHAR) LIKE '0000%'")->update(['created_at' => null]);
                $this->addUpdatedAt($table, 'created_at');
            }
        }
    }

    private function addCreatedAndUpdatedAt(string $table, string $from): void
    {
        $this->sql("ALTER TABLE `{$this->t($table)}` ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT NULL, ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL");
        DB::table($table)->update(['created_at' => DB::raw("`{$from}`"), 'updated_at' => DB::raw("`{$from}`")]);
    }

    private function addUpdatedAt(string $table, ?string $from = null): void
    {
        if (Schema::hasColumn($table, 'updated_at')) {
            return;
        }
        $this->sql("ALTER TABLE `{$this->t($table)}` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL");
        if ($from) {
            DB::table($table)->update(['updated_at' => DB::raw("`{$from}`")]);
        }
    }

    private function convertEnums(): void
    {
        foreach (self::ENUMS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as [$column, $length, $default]) {
                if (! Schema::hasColumn($table, $column) || ! str_starts_with(strtolower($this->columnType($table, $column)), 'enum')) {
                    continue;
                }
                $null = $this->isNullable($table, $column) ? 'NULL' : 'NOT NULL';
                $this->sql("ALTER TABLE `{$this->t($table)}` MODIFY `{$column}` VARCHAR({$length}) {$null} DEFAULT '{$default}'");
            }
        }
    }

    public function down(): void
    {
        // Not reversible on purpose: restore the pre-deploy backup instead.
    }
};
