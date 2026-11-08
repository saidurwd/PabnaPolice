<?php

use App\Support\BreakingMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4, step 4: real foreign keys and the indexes the public pages need.
 *
 * Before each key is added the child rows are checked for references to rows that do not exist. If any are found the
 * migration stops WITHOUT changing that key (a clear list is printed): data is never deleted or edited to make a key fit.
 * `created_by` / audit `user_id` deliberately get no key: they record who did something and must survive the user's deletion.
 */
return new class extends BreakingMigration
{
    /** [child table, column, parent table, on delete] */
    private const KEYS = [
        ['contents', 'category_id', 'categories', 'restrict'],
        ['categories', 'parent_id', 'categories', 'restrict'],
        ['banners', 'category_id', 'banner_categories', 'restrict'],
        ['banner_categories', 'parent_id', 'banner_categories', 'restrict'],
        ['documents', 'category_id', 'document_categories', 'restrict'],
        ['document_categories', 'parent_id', 'document_categories', 'restrict'],
        ['menus', 'parent_id', 'menus', 'restrict'],
        ['users', 'group_id', 'user_groups', 'restrict'],
        ['users', 'status_id', 'user_statuses', 'restrict'],
        ['customers', 'group_id', 'user_groups', 'restrict'],
        ['customers', 'status_id', 'user_statuses', 'restrict'],
        ['acl_rules', 'group_id', 'user_groups', 'cascade'],
        ['acl_actions', 'controller_id', 'acl_controllers', 'cascade'],
        ['states', 'country_id', 'countries', 'restrict'],
        ['cities', 'country_id', 'countries', 'restrict'],
        ['cities', 'state_id', 'states', 'restrict'],
        ['districts', 'country_id', 'countries', 'restrict'],
        ['districts', 'state_id', 'states', 'restrict'],
        ['districts', 'city_id', 'cities', 'restrict'],
        ['thanas', 'country_id', 'countries', 'restrict'],
        ['thanas', 'state_id', 'states', 'restrict'],
        ['thanas', 'city_id', 'cities', 'restrict'],
        ['thanas', 'district_id', 'districts', 'restrict'],
    ];

    public function up(): void
    {
        $this->ensureYiiRetired();

        // 1. Find every problem first, so nothing is half-applied.
        $problems = [];
        foreach (self::KEYS as [$child, $column, $parent]) {
            $orphans = DB::table($child)->whereNotNull($column)
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from($parent.' as p')->whereColumn('p.id', "{$child}.{$column}"))
                ->pluck('id')->all();
            if ($orphans) {
                $problems[] = "{$child}.{$column} points at missing {$parent} rows (ids ".implode(',', array_slice($orphans, 0, 20)).(count($orphans) > 20 ? ', ...' : '').')';
            }
        }
        if ($problems) {
            throw new RuntimeException("Foreign keys not added; fix these rows first:\n - ".implode("\n - ", $problems));
        }

        // 2. Add the keys (making the child column the same type as the parent's id, which MySQL requires).
        $before = $this->rowCounts(array_unique(array_column(self::KEYS, 0)));

        $this->withRelaxedSqlMode(function () {
            foreach (self::KEYS as [$child, $column, $parent, $onDelete]) {
                if ($this->hasForeignKey($child, $column)) {
                    continue;
                }

                $parentType = $this->columnType($parent, 'id');
                if (strtolower($this->columnType($child, $column)) !== strtolower($parentType)) {
                    $null = $this->isNullable($child, $column) ? 'NULL' : 'NOT NULL';
                    $this->sql("ALTER TABLE `{$this->t($child)}` MODIFY `{$column}` {$parentType} {$null}");
                }

                Schema::table($child, function (Blueprint $table) use ($column, $parent, $onDelete) {
                    $fk = $table->foreign($column)->references('id')->on($parent);
                    $onDelete === 'cascade' ? $fk->cascadeOnDelete() : $fk->restrictOnDelete();
                });
            }
        });

        // 3. Indexes for the queries the website runs on every page.
        $this->index('contents', ['category_id', 'status', 'published_at'], 'contents_listing_index');
        $this->index('documents', ['category_id', 'status', 'published_at'], 'documents_listing_index');
        $this->index('banners', ['category_id', 'status'], 'banners_listing_index');
        $this->index('visitors', ['server_time'], 'visitors_server_time_index');
        $this->index('audit_trails', ['user_id'], 'audit_trails_user_id_index');
        $this->index('audit_trails', ['login_time'], 'audit_trails_login_time_index');

        $this->assertSameRowCounts($before);
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        // Raw SQL: the query builder would prefix "information_schema" with os_.
        return DB::selectOne(
            'select 1 as found from information_schema.key_column_usage
             where table_schema = database() and table_name = ? and column_name = ? and referenced_table_name is not null limit 1',
            [$this->t($table), $column]
        ) !== null;
    }

    /** @param  array<int, string>  $columns */
    private function index(string $table, array $columns, string $name): void
    {
        if (Schema::hasIndex($table, $name)) {
            return;
        }
        Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
    }

    public function down(): void
    {
        // Not reversible on purpose: restore the pre-deploy backup instead.
    }
};
