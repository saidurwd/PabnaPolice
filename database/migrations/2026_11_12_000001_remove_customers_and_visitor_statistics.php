<?php

use App\Support\BreakingMigration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes two modules nobody uses: "Customers" (an empty leftover of the Yii template) and "Visitor statistics"
 * (an admin page-view log).
 *
 * BEFORE anything is deleted the two tables are dumped to
 * storage/app/private/removed-tables/<timestamp>.sql (and their menu/permission rows to a -extra.json next to it).
 * If the dump cannot be written or does not contain both tables, the migration stops and changes nothing.
 * To bring them back, import that .sql file into the database.
 */
return new class extends BreakingMigration
{
    private const TABLES = ['customers', 'visitors'];

    public function up(): void
    {
        $this->ensureYiiRetired();

        $existing = array_values(array_filter(self::TABLES, fn ($t) => Schema::hasTable($t)));
        $configRows = $this->configRows();

        if ($existing || array_filter($configRows)) {
            $this->backupTables($existing, 'customers-visitors', $configRows);
        }

        DB::transaction(function () use ($configRows) {
            // Menu entries and permissions of the removed modules.
            DB::table('menus')->whereIn('id', array_column($configRows['menus'], 'id'))->delete();
            DB::table('acl_rules')->whereIn('controller', ['customer', 'visitor'])->delete();
            $moduleIds = array_column($configRows['acl_controllers'], 'id');
            DB::table('acl_actions')->whereIn('controller_id', $moduleIds)->delete();
            DB::table('acl_controllers')->whereIn('id', $moduleIds)->delete();
        });

        foreach ($existing as $table) {   // customers first: it has foreign keys, nothing references it
            Schema::dropIfExists($table);
        }
    }

    /** @return array{menus: array<int, mixed>, acl_rules: array<int, mixed>, acl_controllers: array<int, mixed>, acl_actions: array<int, mixed>} */
    private function configRows(): array
    {
        $modules = DB::table('acl_controllers')->whereIn('controller', ['customer', 'visitor'])->get();

        return [
            'menus' => DB::table('menus')->where(fn ($q) => $q->whereIn('controller', ['customer', 'visitor'])->orWhereIn('url', ['/customer/admin', '/visitor/admin']))->get()->map(fn ($r) => (array) $r)->all(),
            'acl_rules' => DB::table('acl_rules')->whereIn('controller', ['customer', 'visitor'])->get()->map(fn ($r) => (array) $r)->all(),
            'acl_controllers' => $modules->map(fn ($r) => (array) $r)->all(),
            'acl_actions' => DB::table('acl_actions')->whereIn('controller_id', $modules->pluck('id'))->get()->map(fn ($r) => (array) $r)->all(),
        ];
    }

    public function down(): void
    {
        // Not reversible on purpose: import the dump in storage/app/private/removed-tables/ to bring the tables back.
    }
};
