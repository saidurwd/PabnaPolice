<?php

namespace App\Admin;

use App\Models\AclController;
use App\Models\BannerCategory;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\DocumentCategory;
use App\Models\Menu;
use App\Models\SiteLink;
use App\Models\State;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserStatus;

/** Select-box option sources used by the resource definitions (id => label). */
class Options
{
    private static array $memo = [];

    public static function get(string|array $source): array
    {
        if (is_array($source)) {
            return $source;
        }

        return self::$memo[$source] ??= match ($source) {
            'status' => ['Active' => 'Active', 'Inactive' => 'Inactive'],
            'yesno' => ['No' => 'No', 'Yes' => 'Yes'],
            'user_flag' => [1 => 'Enabled', 0 => 'Disabled'],
            'group_type' => ['Back' => 'Back (admin panel)', 'Front' => 'Front (website)'],
            'categories' => self::tree(Category::class),
            'banner_categories' => self::tree(BannerCategory::class),
            'document_categories' => self::tree(DocumentCategory::class),
            'user_groups' => UserGroup::orderBy('id')->pluck('title', 'id')->all(),
            'user_statuses' => UserStatus::orderBy('id')->pluck('title', 'id')->all(),
            'users' => User::orderBy('full_name')->pluck('full_name', 'id')->all(),
            'countries' => Country::orderBy('title')->pluck('title', 'id')->all(),
            'states' => State::orderBy('title')->pluck('title', 'id')->all(),
            'cities' => City::orderBy('title')->pluck('title', 'id')->all(),
            'districts' => District::orderBy('title')->pluck('title', 'id')->all(),
            'menu_parents' => Menu::whereNull('parent_id')->orderBy('ordering')->pluck('title', 'id')->all(),
            'acl_controllers' => AclController::orderBy('title')->pluck('title', 'id')->all(),
            'link_groups' => SiteLink::GROUPS,
            default => throw new \InvalidArgumentException("Unknown option source [{$source}]"),
        };
    }

    /** Category-style tables: label is the full "Parent/Child" alias, sorted by path. */
    private static function tree(string $model): array
    {
        return $model::orderBy('alias')->get()->mapWithKeys(
            fn ($c) => [$c->id => str_replace('/', ' › ', trim($c->alias ?: $c->title))]
        )->all();
    }

    public static function forget(): void
    {
        self::$memo = [];
    }
}
