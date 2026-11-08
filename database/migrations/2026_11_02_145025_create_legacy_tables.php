<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline of the tables that the old Yii application already created.
 *
 * LIVE DATABASE SAFETY: every table is created only if it does not exist yet, so running
 * this on the live (Yii) database changes nothing and never touches data. It exists so that a
 * brand-new environment (dev, CI, staging) can be built from migrations alone.
 * down() is intentionally empty: rolling back must never drop live content.
 *
 * The structure mirrors the live schema (same names, same columns, `os_` prefix via DB_PREFIX).
 */
return new class extends Migration
{
    private function make(string $name, Closure $callback): void
    {
        if (Schema::hasTable($name)) {
            return;
        }

        Schema::create($name, function (Blueprint $table) use ($callback) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';
            $callback($table);
        });
    }

    public function up(): void
    {
        $this->make('acl', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('group_id')->nullable();
            $t->string('controller', 150)->nullable();
            $t->string('actions', 150)->nullable();
            $t->string('action_title', 150)->nullable();
            $t->tinyInteger('access')->nullable()->default(0);
            $t->tinyInteger('controller_type')->nullable();
        });

        $this->make('acl_action', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('controller_id')->nullable();
            $t->string('title', 150)->nullable();
            $t->string('action', 150)->nullable();
            $t->tinyInteger('controller_type')->nullable();
        });

        $this->make('acl_controller', function (Blueprint $t) {
            $t->increments('id');
            $t->string('controller', 150)->nullable();
            $t->string('title', 150)->nullable();
            $t->tinyInteger('controller_type')->nullable();
            $t->tinyInteger('status')->nullable()->default(1);
        });

        $this->make('audit_trail', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('user_id');
            $t->timestamp('login_time')->nullable();
            $t->timestamp('logout_time')->nullable();
        });

        $this->make('banner_category', function (Blueprint $t) {
            $t->integer('id', true);
            $t->integer('parent')->nullable();
            $t->string('title');
            $t->text('details')->nullable();
            $t->string('alias')->nullable()->index('idx_alias');
            $t->string('path')->nullable();
            $t->enum('status', ['Active', 'Inactive'])->default('Active')->index('cat_idx');
            $t->integer('created_by')->nullable();
            $t->timestamp('created_on')->nullable();
        });

        $this->make('banner', function (Blueprint $t) {
            $t->integer('id', true);
            $t->integer('category')->index('category');
            $t->string('title');
            $t->string('banner')->nullable();
            $t->string('clickurl')->nullable();
            $t->text('details')->nullable();
            $t->enum('status', ['Active', 'Inactive'])->nullable()->default('Active');
            $t->enum('sticky', ['Yes', 'No'])->nullable()->default('No');
            $t->integer('ordering')->nullable()->default(0);
            $t->integer('created_by')->nullable();
            $t->timestamp('created_on')->nullable();
        });

        $this->make('category', function (Blueprint $t) {
            $t->integer('id', true);
            $t->integer('parent')->nullable();
            $t->string('title');
            $t->string('alias')->nullable();
            $t->text('details')->nullable();
            $t->string('path')->nullable();
            $t->enum('listing', ['Yes', 'No'])->nullable()->default('No');
            $t->enum('status', ['Active', 'Inactive'])->default('Active')->index('idx_status');
            $t->integer('created_by')->nullable();
            $t->timestamp('created_on')->useCurrent()->useCurrentOnUpdate();
        });

        $this->make('content', function (Blueprint $t) {
            $t->integer('id', true);
            $t->string('title');
            $t->text('details')->nullable();
            $t->integer('category')->index('idx_category');
            $t->string('image', 250)->nullable();
            $t->enum('featured', ['Yes', 'No'])->nullable()->default('No');
            $t->integer('ordering')->nullable()->default(0);
            $t->enum('status', ['Active', 'Inactive'])->nullable()->default('Active')->index('idx_status');
            $t->text('metakey')->nullable();
            $t->text('metadesc')->nullable();
            $t->integer('hits')->nullable()->default(0);
            $t->integer('created_by')->default(0);
            $t->timestamp('created_on')->useCurrent()->useCurrentOnUpdate();
        });

        $this->make('country', function (Blueprint $t) {
            $t->integer('id', true);
            $t->string('title');
            $t->char('country_2_code', 2)->nullable()->index('idx_country_2_code');
            $t->char('country_3_code', 3)->nullable()->index('idx_country_3_code');
            $t->enum('status', ['Active', 'Inactive'])->nullable()->default('Active');
        });

        $this->make('state', function (Blueprint $t) {
            $t->integer('id', true);
            $t->integer('country');
            $t->string('title', 192);
            $t->string('state_2_code', 6)->nullable();
            $t->string('state_3_code', 9)->nullable();
            $t->enum('status', ['Active', 'Inactive'])->nullable()->default('Active');
        });

        $this->make('city', function (Blueprint $t) {
            $t->integer('id', true);
            $t->integer('country');
            $t->integer('state');
            $t->string('title');
            $t->string('city_2_code', 6)->nullable();
            $t->string('city_3_code', 9)->nullable();
            $t->enum('status', ['Active', 'Inactive'])->nullable()->default('Active');
        });

        $this->make('district', function (Blueprint $t) {
            $t->integer('id', true);
            $t->integer('country')->default(18);
            $t->integer('state');
            $t->integer('city');
            $t->string('title', 150);
            $t->enum('status', ['Active', 'Inactive'])->default('Active');
        });

        $this->make('thana', function (Blueprint $t) {
            $t->integer('id', true);
            $t->integer('country')->default(18);
            $t->integer('state');
            $t->integer('city');
            $t->integer('district');
            $t->string('title', 100);
            $t->enum('status', ['Active', 'Inactive'])->default('Active');
        });

        $this->make('customer', function (Blueprint $t) {
            $t->integer('id', true);
            $t->string('full_name', 150)->index('idx_name');
            $t->string('username', 100)->index('username');
            $t->string('email', 100)->index('email');
            $t->string('password', 100);
            $t->timestamp('register_date')->nullable();
            $t->dateTime('lastvisit')->nullable();
            $t->string('activation', 100)->nullable();
            $t->integer('group')->nullable();
            $t->integer('status')->nullable()->default(1)->index('status');
        });

        $this->make('document_category', function (Blueprint $t) {
            $t->integer('id', true);
            $t->integer('parent')->nullable();
            $t->string('title');
            $t->text('details')->nullable();
            $t->string('alias')->nullable();
            $t->string('path')->nullable();
            $t->enum('status', ['Active', 'Inactive'])->nullable()->default('Active');
            $t->integer('created_by')->nullable();
            $t->timestamp('created_on')->nullable();
        });

        $this->make('document', function (Blueprint $t) {
            $t->integer('id', true);
            $t->integer('category')->index('cetegory');
            $t->string('title');
            $t->string('document_file')->nullable();
            $t->string('document_type', 50)->nullable();
            $t->string('document_size', 50)->nullable();
            $t->text('details')->nullable();
            $t->enum('status', ['Active', 'Inactive'])->nullable()->default('Active');
            $t->integer('created_by')->nullable();
            $t->timestamp('created_on')->nullable();
        });

        $this->make('menu', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('parent')->nullable()->default(0);
            $t->string('title', 150);
            $t->string('controller', 50)->nullable();
            $t->string('url', 100);
            $t->string('icon', 50)->nullable();
            $t->integer('ordering')->nullable();
            $t->tinyInteger('status')->nullable()->default(1);
            $t->string('group', 250)->nullable();
        });

        $this->make('user_group', function (Blueprint $t) {
            $t->integer('id', true);
            $t->enum('group_type', ['Front', 'Back'])->default('Front');
            $t->string('title', 150)->unique('idx_usergroup_parent_title_lookup');
            $t->text('details')->nullable();
        });

        $this->make('user_status', function (Blueprint $t) {
            $t->integer('id', true);
            $t->string('title', 100);
        });

        $this->make('user', function (Blueprint $t) {
            $t->integer('id', true);
            $t->string('full_name', 150)->index('idx_name');
            $t->string('username', 100)->index('username');
            $t->string('email', 100)->index('email');
            $t->string('password', 100);
            $t->timestamp('register_date')->nullable();
            $t->dateTime('lastvisit')->nullable();
            $t->string('activation', 100)->nullable();
            $t->integer('group_id')->nullable();
            $t->integer('status')->nullable()->default(1)->index('status');
            $t->string('picture')->nullable();
            $t->string('photo');
        });

        $this->make('visitor', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->tinyInteger('user_type')->nullable();
            $t->integer('user_id')->nullable();
            $t->string('user_name', 100)->nullable();
            $t->string('page_title')->nullable();
            $t->string('page_link', 150)->nullable();
            $t->timestamp('server_time')->nullable();
            $t->string('browser', 100)->nullable();
            $t->string('visitor_ip', 100)->nullable();
        });
    }

    public function down(): void
    {
        // Intentionally empty: never drop the live content tables.
    }
};
