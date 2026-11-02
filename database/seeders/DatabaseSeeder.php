<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Demo data for an EMPTY local database only. Refuses to run in production or on a database that has content. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() || Content::query()->exists()) {
            $this->command?->warn('Skipped: this database already has content (or APP_ENV=production).');

            return;
        }

        DB::table('user_groups')->insertOrIgnore([
            ['id' => 1, 'group_type' => 'Back', 'title' => 'Super Users'],
            ['id' => 2, 'group_type' => 'Back', 'title' => 'Administrator'],
        ]);
        DB::table('user_statuses')->insertOrIgnore([['id' => 1, 'title' => 'Active']]);

        User::factory()->create(['username' => 'admin', 'email' => 'admin@example.test', 'full_name' => 'Demo Admin', 'password' => sha1('admin12345')]);

        $categories = Category::factory(3)->create();
        $categories->each(fn ($c) => Content::factory(5)->create(['category_id' => $c->id]));
        Banner::factory(3)->create(['category_id' => 1]);

        $this->command?->info('Demo data created. Admin login: admin / admin12345');
    }
}
