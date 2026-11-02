<?php

namespace App\Console\Commands;

use App\Support\PermissionCatalog;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class PermissionsSync extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Create any permissions the admin screens need that do not exist yet (run on every deploy)';

    public function handle(): int
    {
        $created = PermissionCatalog::sync();

        // The super role is always complete, so its permission list also shows everything in the role editor.
        Role::findOrCreate(config('admin.super_role'), 'web')->syncPermissions(PermissionCatalog::names());

        $this->info($created ? "Created {$created} new permission(s)." : 'Permissions are up to date.');
        $this->line('New permissions are not granted to any role except '.config('admin.super_role').': assign them under Roles & permissions.');

        return self::SUCCESS;
    }
}
