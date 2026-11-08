<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * New table for the link lists that were hardcoded in the Yii theme (homepage buttons,
 * side panels, widgets, important-links tiles, footer links, app badges). Seeded once.
 */
return new class extends Migration
{
    public function up(): void
    {
        $fresh = ! Schema::hasTable('site_link');

        if ($fresh) {
            Schema::create('site_link', function (Blueprint $table) {
                $table->increments('id');
                $table->string('group', 50)->index();
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->string('url', 500)->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_extra')->default(false);
                $table->integer('ordering')->default(0);
                $table->enum('status', ['Active', 'Inactive'])->default('Active');
                $table->timestamps();
            });
        }

        // Seed only when the table was just created, so admin edits are never overwritten.
        if ($fresh) {
            foreach (require database_path('data/site_links.php') as $row) {
                DB::table('site_link')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_link');
    }
};
