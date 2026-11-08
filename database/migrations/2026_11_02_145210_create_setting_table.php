<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * New table for editable site settings (replaces values that were hardcoded in the Yii
 * config and theme). Seeds the old hardcoded values; existing keys are never overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setting')) {
            Schema::create('setting', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100)->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        foreach (config('site') as $fields) {
            foreach ($fields as $name => $def) {
                DB::table('setting')->insertOrIgnore([
                    'name' => $name,
                    'value' => $def['default'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('setting');
    }
};
