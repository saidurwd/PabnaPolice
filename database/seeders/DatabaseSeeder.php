<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Content;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/** Demo data for an EMPTY local database only. Refuses to run in production or on a database that has content. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() || Content::query()->exists()) {
            $this->command?->warn('Skipped: this database already has content (or APP_ENV=production).');

            return;
        }

        PermissionCatalog::sync();
        Role::findOrCreate(config('admin.super_role'), 'web')->syncPermissions(PermissionCatalog::names());
        Role::findOrCreate('Editor', 'web')->syncPermissions(['content.view', 'content.create', 'content.update', 'category.view', 'banner.view', 'banner.create', 'banner.update', 'document.view', 'document.create', 'document.update']);
        DB::table('user_statuses')->insertOrIgnore([['id' => 1, 'title' => 'Active']]);

        User::factory()->superUser()->create(['username' => 'admin', 'email' => 'admin@example.test', 'full_name' => 'Demo Admin', 'password' => sha1('admin12345')]);

        $categories = Category::factory(3)->create();
        $categories->each(fn ($c) => Content::factory(5)->create(['category_id' => $c->id]));
        Banner::factory(3)->create(['category_id' => 1]);

        $this->command?->info('Demo data created. Admin login: admin / admin12345');
    }
}
