<?php

use App\Support\BreakingMigration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Removes the "Places" module (countries, states, cities, districts, thanas): reference data the website never used.
 *
 * The five tables are dumped to storage/app/private/removed-tables/<timestamp>-places.sql BEFORE anything is deleted
 * (the migration stops, changing nothing, if the dump is incomplete). Their menu entries and permissions go with them.
 * To bring the data back, import that .sql file.
 */
return new class extends BreakingMigration
{
    /** Child tables first: each has foreign keys to the ones after it. */
    private const TABLES = ['thanas', 'districts', 'cities', 'states', 'countries'];

    private const SLUGS = ['country', 'state', 'city', 'district', 'thana'];

    public function up(): void
    {
        $this->ensureYiiRetired();

        $existing = array_values(array_filter(self::TABLES, fn ($t) => Schema::hasTable($t)));
        $permissionIds = DB::table('permissions')->where(function ($q) {
            foreach (self::SLUGS as $slug) {
                $q->orWhere('name', 'like', $slug.'.%');
            }
        })->pluck('id')->all();

        $menus = DB::table('menus')->whereIn('url', array_map(fn ($s) => "/{$s}", self::SLUGS))->orWhereIn('url', array_map(fn ($s) => "/{$s}/admin", self::SLUGS))->get();

        if ($existing) {
            $this->backupTables($existing, 'places', [
                'menus' => $menus->map(fn ($r) => (array) $r)->all(),
                'permission_ids' => $permissionIds,
            ]);
        }

        DB::transaction(function () use ($menus, $permissionIds) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();

            DB::table('menus')->whereIn('id', $menus->pluck('id'))->delete();
            // The "Reference data" heading they lived under, if nothing else is left in it.
            $heading = DB::table('menus')->whereNull('parent_id')->where('url', '#')->where('title', 'Reference data')->first();
            if ($heading && ! DB::table('menus')->where('parent_id', $heading->id)->exists()) {
                DB::table('menus')->where('id', $heading->id)->delete();
            }
        });

        foreach ($existing as $table) {
            Schema::dropIfExists($table);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Not reversible on purpose: import the dump in storage/app/private/removed-tables/ to bring the tables back.
    }
};
