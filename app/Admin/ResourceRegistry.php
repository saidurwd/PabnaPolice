<?php

namespace App\Admin;

use App\Models\Acl;
use App\Models\AclAction;
use App\Models\AclController;
use App\Models\AuditTrail;
use App\Models\Banner;
use App\Models\BannerCategory;
use App\Models\Category;
use App\Models\City;
use App\Models\Content;
use App\Models\Country;
use App\Models\Customer;
use App\Models\District;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Feedback;
use App\Models\Menu;
use App\Models\SiteLink;
use App\Models\State;
use App\Models\Thana;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserStatus;
use App\Models\Visitor;

/**
 * Declarative description of every admin CRUD screen. The generic ResourceController and the
 * shared Blade views are driven entirely by this list.
 *
 * Field types: text, email, number, textarea, richtext, select, image, file, password, bool, datetime
 * Column types: text (default), limit, badge, option, image, datetime, bool
 */
class ResourceRegistry
{
    private static ?array $all = null;

    public static function all(): array
    {
        return self::$all ??= self::build();
    }

    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /** Resource by legacy ACL/menu controller name ("bannerCategory", "Country", ...). */
    public static function findByAcl(string $name): ?array
    {
        foreach (self::all() as $def) {
            if (strcasecmp($def['acl'], $name) === 0) {
                return $def;
            }
        }

        return null;
    }

    private static function build(): array
    {
        $status = ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => 'status', 'rules' => 'required|in:Active,Inactive'];
        $ordering = ['name' => 'ordering', 'label' => 'Ordering', 'type' => 'number', 'rules' => 'nullable|integer', 'help' => 'Lower numbers first.'];
        $published = ['name' => 'published_at', 'label' => 'Published on', 'type' => 'datetime', 'rules' => 'nullable|date'];

        $tree = fn (string $source, string $parentLabel = 'Parent') => [
            'name' => 'parent_id', 'label' => $parentLabel, 'type' => 'select', 'options' => $source,
            'rules' => 'nullable|integer', 'empty' => '— None (top level) —',
        ];

        $geo = fn (string $name, string $label, string $source) => [
            'name' => $name, 'label' => $label, 'type' => 'select', 'options' => $source, 'rules' => 'required|integer',
        ];

        $list = [
            // ---------------------------------------------------------------- content
            'content' => [
                'softDeletes' => true,
                'acl' => 'content', 'model' => Content::class, 'title' => 'Content', 'plural' => 'Contents', 'icon' => 'bi-file-earmark-text',
                'order' => ['published_at', 'desc'], 'search' => ['title', 'details'], 'audit' => true, 'perPage' => 25,
                'filters' => ['category_id' => ['Category', 'categories'], 'status' => ['Status', 'status']],
                'columns' => [
                    ['id', 'ID'], ['title', 'Title', 'limit', 70], ['category_id', 'Category', 'option', 'categories'],
                    ['featured', 'Featured'], ['status', 'Status', 'badge'], ['hits', 'Hits'], ['published_at', 'Published', 'datetime'],
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:255'],
                    ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => 'categories', 'rules' => 'required|integer'],
                    ['name' => 'details', 'label' => 'Details', 'type' => 'richtext', 'rules' => 'nullable'],
                    ['name' => 'image', 'label' => 'Image', 'type' => 'image', 'dir' => 'content', 'rules' => 'nullable|image|max:8192'],
                    ['name' => 'featured', 'label' => 'Featured', 'type' => 'select', 'options' => 'yesno', 'rules' => 'nullable|in:Yes,No'],
                    $ordering, $status,
                    ['name' => 'metakey', 'label' => 'Meta keywords', 'type' => 'textarea', 'rules' => 'nullable'],
                    ['name' => 'metadesc', 'label' => 'Meta description', 'type' => 'textarea', 'rules' => 'nullable'],
                    $published,
                ],
            ],
            'category' => [
                'acl' => 'category', 'model' => Category::class, 'title' => 'Category', 'plural' => 'Categories', 'icon' => 'bi-diagram-3',
                'order' => ['alias', 'asc'], 'search' => ['title', 'alias'], 'audit' => true, 'tree' => true,
                'filters' => ['status' => ['Status', 'status']],
                'columns' => [['id', 'ID'], ['alias', 'Category', 'limit', 80], ['listing', 'Listing'], ['status', 'Status', 'badge']],
                'fields' => [
                    $tree('categories'),
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:255'],
                    ['name' => 'details', 'label' => 'Details', 'type' => 'textarea', 'rules' => 'nullable'],
                    ['name' => 'listing', 'label' => 'Show sub-menu listing', 'type' => 'select', 'options' => 'yesno', 'rules' => 'nullable|in:Yes,No'],
                    $status,
                ],
                'guard' => fn ($m) => Category::where('parent_id', $m->id)->exists() ? 'It has sub-categories.'
                    : (Content::withTrashed()->where('category_id', $m->id)->exists() ? 'It still has content.' : null),
            ],
            // ---------------------------------------------------------------- banners
            'banner' => [
                'softDeletes' => true,
                'acl' => 'banner', 'model' => Banner::class, 'title' => 'Banner', 'plural' => 'Banners', 'icon' => 'bi-images',
                'order' => ['id', 'desc'], 'search' => ['title'], 'audit' => true,
                'filters' => ['category_id' => ['Category', 'banner_categories'], 'status' => ['Status', 'status']],
                'columns' => [
                    ['id', 'ID'], ['banner', 'Image', 'image', 'banner'], ['title', 'Title', 'limit', 60],
                    ['category_id', 'Category', 'option', 'banner_categories'], ['status', 'Status', 'badge'], ['ordering', 'Order'],
                ],
                'fields' => [
                    ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => 'banner_categories', 'rules' => 'required|integer'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:255'],
                    ['name' => 'banner', 'label' => 'Image', 'type' => 'image', 'dir' => 'banner', 'rules' => 'nullable|image|max:8192'],
                    ['name' => 'clickurl', 'label' => 'Click URL', 'type' => 'text', 'rules' => 'nullable|max:255'],
                    ['name' => 'details', 'label' => 'Details', 'type' => 'textarea', 'rules' => 'nullable'],
                    ['name' => 'sticky', 'label' => 'Sticky', 'type' => 'select', 'options' => 'yesno', 'rules' => 'nullable|in:Yes,No'],
                    $ordering, $status, $published,
                ],
            ],
            'banner-category' => [
                'acl' => 'bannerCategory', 'model' => BannerCategory::class, 'title' => 'Banner category', 'plural' => 'Banner categories', 'icon' => 'bi-collection',
                'order' => ['id', 'asc'], 'search' => ['title'], 'audit' => true, 'tree' => true,
                'columns' => [['id', 'ID'], ['alias', 'Category', 'limit', 80], ['status', 'Status', 'badge']],
                'fields' => [
                    $tree('banner_categories'),
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:255'],
                    ['name' => 'details', 'label' => 'Details', 'type' => 'textarea', 'rules' => 'nullable'],
                    $status,
                ],
                'guard' => fn ($m) => BannerCategory::where('parent_id', $m->id)->exists() ? 'It has sub-categories.'
                    : (Banner::withTrashed()->where('category_id', $m->id)->exists() ? 'It still has banners.' : null),
            ],
            // ---------------------------------------------------------------- documents
            'document' => [
                'softDeletes' => true,
                'acl' => 'document', 'model' => Document::class, 'title' => 'Document', 'plural' => 'Documents', 'icon' => 'bi-file-earmark-pdf',
                'order' => ['published_at', 'desc'], 'search' => ['title'], 'audit' => true,
                'filters' => ['category_id' => ['Category', 'document_categories'], 'status' => ['Status', 'status']],
                'columns' => [
                    ['id', 'ID'], ['title', 'Title', 'limit', 70], ['category_id', 'Category', 'option', 'document_categories'],
                    ['document_type', 'Type'], ['status', 'Status', 'badge'], ['published_at', 'Published', 'datetime'],
                ],
                'fields' => [
                    ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => 'document_categories', 'rules' => 'required|integer'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:255'],
                    ['name' => 'document_file', 'label' => 'File', 'type' => 'file', 'dir' => 'document', 'rules' => 'nullable|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,txt'],
                    ['name' => 'details', 'label' => 'Details', 'type' => 'textarea', 'rules' => 'nullable'],
                    $status, $published,
                ],
            ],
            'document-category' => [
                'acl' => 'documentCategory', 'model' => DocumentCategory::class, 'title' => 'Document category', 'plural' => 'Document categories', 'icon' => 'bi-folder',
                'order' => ['id', 'asc'], 'search' => ['title'], 'audit' => true, 'tree' => true,
                'columns' => [['id', 'ID'], ['alias', 'Category', 'limit', 80], ['status', 'Status', 'badge']],
                'fields' => [
                    $tree('document_categories'),
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:255'],
                    ['name' => 'details', 'label' => 'Details', 'type' => 'textarea', 'rules' => 'nullable'],
                    $status,
                ],
                'guard' => fn ($m) => DocumentCategory::where('parent_id', $m->id)->exists() ? 'It has sub-categories.'
                    : (Document::withTrashed()->where('category_id', $m->id)->exists() ? 'It still has documents.' : null),
            ],
            // ---------------------------------------------------------------- site
            'site-link' => [
                'acl' => 'siteLink', 'model' => SiteLink::class, 'title' => 'Site link', 'plural' => 'Site links', 'icon' => 'bi-link-45deg',
                'order' => ['group', 'asc', 'ordering'], 'search' => ['title', 'url'],
                'filters' => ['group' => ['Group', 'link_groups'], 'status' => ['Status', 'status']],
                'columns' => [
                    ['id', 'ID'], ['group', 'Group', 'option', 'link_groups'], ['image', 'Image', 'image', 'path'],
                    ['title', 'Title', 'limit', 50], ['url', 'URL', 'limit', 50], ['ordering', 'Order'], ['status', 'Status', 'badge'],
                ],
                'fields' => [
                    ['name' => 'group', 'label' => 'Where it appears', 'type' => 'select', 'options' => 'link_groups', 'rules' => 'required'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:255'],
                    ['name' => 'subtitle', 'label' => 'Sub-title', 'type' => 'text', 'rules' => 'nullable|max:255', 'help' => 'Used by the "Important links" tiles.'],
                    ['name' => 'url', 'label' => 'URL', 'type' => 'text', 'rules' => 'nullable|max:500', 'help' => 'Full address (https://…) or a site path such as /content/14.html'],
                    ['name' => 'image', 'label' => 'Icon / image', 'type' => 'image', 'dir' => 'links', 'store' => 'path', 'rules' => 'nullable|image|max:4096'],
                    ['name' => 'is_extra', 'label' => 'Hidden until "Show more" (Important links only)', 'type' => 'bool', 'rules' => 'nullable|boolean'],
                    ['name' => 'ordering', 'label' => 'Ordering', 'type' => 'number', 'rules' => 'nullable|integer'],
                    $status,
                ],
            ],
            'feedback' => [
                'acl' => 'feedback', 'model' => Feedback::class, 'title' => 'Feedback', 'plural' => 'Feedback', 'icon' => 'bi-chat-dots',
                'order' => ['id', 'desc'], 'search' => ['name', 'email', 'message'], 'actions' => ['index', 'edit', 'destroy'],
                'columns' => [
                    ['id', 'ID'], ['name', 'Name'], ['email', 'Email'], ['phone', 'Phone'], ['message', 'Message', 'limit', 60],
                    ['is_read', 'Read', 'bool'], ['created_at', 'Received', 'datetime'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'readonly' => true],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'text', 'readonly' => true],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'readonly' => true],
                    ['name' => 'nid', 'label' => 'NID', 'type' => 'text', 'readonly' => true],
                    ['name' => 'subject', 'label' => 'Subject', 'type' => 'text', 'readonly' => true],
                    ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'readonly' => true],
                    ['name' => 'ip', 'label' => 'IP', 'type' => 'text', 'readonly' => true],
                    ['name' => 'is_read', 'label' => 'Mark as read', 'type' => 'bool', 'rules' => 'nullable|boolean'],
                ],
            ],
            // ---------------------------------------------------------------- access control
            'user' => [
                'acl' => 'user', 'model' => User::class, 'title' => 'User', 'plural' => 'Users', 'icon' => 'bi-person',
                'order' => ['id', 'asc'], 'search' => ['full_name', 'username', 'email'],
                'filters' => ['group_id' => ['Group', 'user_groups'], 'status_id' => ['Status', 'user_statuses']],
                'columns' => [
                    ['id', 'ID'], ['photo', 'Photo', 'image', 'user'], ['full_name', 'Name'], ['username', 'Username'], ['email', 'Email'],
                    ['group_id', 'Group', 'option', 'user_groups'], ['status_id', 'Status', 'option', 'user_statuses'], ['last_login_at', 'Last sign-in', 'datetime'],
                ],
                'fields' => [
                    ['name' => 'full_name', 'label' => 'Full name', 'type' => 'text', 'rules' => 'required|max:150'],
                    ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'rules' => 'required|max:100|unique:users,username,{id}'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => 'required|email|max:100|unique:users,email,{id}'],
                    ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'rules' => 'nullable|min:6|max:100', 'required_on' => 'create', 'help' => 'Leave blank to keep the current password.'],
                    ['name' => 'group_id', 'label' => 'Group', 'type' => 'select', 'options' => 'user_groups', 'rules' => 'required|integer'],
                    ['name' => 'status_id', 'label' => 'Status', 'type' => 'select', 'options' => 'user_statuses', 'rules' => 'required|integer'],
                    ['name' => 'photo', 'label' => 'Photo', 'type' => 'image', 'dir' => 'user', 'rules' => 'nullable|image|max:4096'],
                ],
                'guard' => fn ($m) => (int) $m->id === (int) auth()->id() ? 'You cannot delete your own account.' : null,
            ],
            'user-group' => [
                'acl' => 'userGroup', 'model' => UserGroup::class, 'title' => 'User group', 'plural' => 'User groups', 'icon' => 'bi-people',
                'order' => ['id', 'asc'], 'search' => ['title'], 'rowLinks' => ['access' => 'Permissions'],
                'columns' => [['id', 'ID'], ['title', 'Title'], ['group_type', 'Type'], ['details', 'Details', 'limit', 60]],
                'fields' => [
                    ['name' => 'group_type', 'label' => 'Type', 'type' => 'select', 'options' => 'group_type', 'rules' => 'required|in:Front,Back'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:150|unique:user_groups,title,{id}'],
                    ['name' => 'details', 'label' => 'Details', 'type' => 'textarea', 'rules' => 'nullable'],
                ],
                'guard' => fn ($m) => (int) $m->id <= 1 ? 'Super Users cannot be deleted.'
                    : (User::where('group_id', $m->id)->exists() ? 'Users still belong to it.' : null),
            ],
            'user-status' => [
                'acl' => 'userStatus', 'model' => UserStatus::class, 'title' => 'User status', 'plural' => 'User statuses', 'icon' => 'bi-toggles',
                'order' => ['id', 'asc'], 'search' => ['title'],
                'columns' => [['id', 'ID'], ['title', 'Title']],
                'fields' => [['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:100']],
                'guard' => fn ($m) => User::where('status_id', $m->id)->exists() ? 'Users still use this status.' : null,
            ],
            'acl-controller' => [
                'acl' => 'aclController', 'model' => AclController::class, 'title' => 'Access module', 'plural' => 'Access modules', 'icon' => 'bi-shield-lock',
                'order' => ['id', 'asc'], 'search' => ['controller', 'title'],
                'columns' => [['id', 'ID'], ['controller', 'Key'], ['title', 'Title'], ['status', 'Status', 'bool']],
                'fields' => [
                    ['name' => 'controller', 'label' => 'Key', 'type' => 'text', 'rules' => 'required|max:150', 'help' => 'Matches the ACL rows and menu entries (e.g. content, bannerCategory).'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:150'],
                    ['name' => 'controller_type', 'label' => 'Type', 'type' => 'number', 'rules' => 'nullable|integer'],
                    ['name' => 'status', 'label' => 'Enabled', 'type' => 'bool', 'rules' => 'nullable|boolean'],
                ],
            ],
            'acl-action' => [
                'acl' => 'aclController', 'model' => AclAction::class, 'title' => 'Access action', 'plural' => 'Access actions', 'icon' => 'bi-key',
                'order' => ['controller_id', 'asc'], 'search' => ['title', 'action'],
                'filters' => ['controller_id' => ['Module', 'acl_controllers']],
                'columns' => [['id', 'ID'], ['controller_id', 'Module', 'option', 'acl_controllers'], ['title', 'Title'], ['action', 'Action']],
                'fields' => [
                    ['name' => 'controller_id', 'label' => 'Module', 'type' => 'select', 'options' => 'acl_controllers', 'rules' => 'required|integer'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:150'],
                    ['name' => 'action', 'label' => 'Action', 'type' => 'text', 'rules' => 'required|max:150', 'help' => 'admin, create, update or delete.'],
                    ['name' => 'controller_type', 'label' => 'Type', 'type' => 'number', 'rules' => 'nullable|integer'],
                ],
            ],
            'menu' => [
                'acl' => 'menu', 'model' => Menu::class, 'title' => 'Menu item', 'plural' => 'Menus', 'icon' => 'bi-list',
                'order' => ['parent_id', 'asc', 'ordering'], 'search' => ['title', 'url'],
                'columns' => [
                    ['id', 'ID'], ['title', 'Title'], ['parent_id', 'Parent', 'option', 'menu_parents'], ['url', 'URL'], ['ordering', 'Order'], ['status', 'Enabled', 'bool'],
                ],
                'fields' => [
                    ['name' => 'parent_id', 'label' => 'Parent', 'type' => 'select', 'options' => 'menu_parents', 'rules' => 'nullable|integer', 'empty' => '— Top level —'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|max:150'],
                    ['name' => 'controller', 'label' => 'ACL key', 'type' => 'text', 'rules' => 'nullable|max:50', 'help' => 'Item is hidden from users without access to this module.'],
                    ['name' => 'url', 'label' => 'URL', 'type' => 'text', 'rules' => 'required|max:100', 'help' => 'Legacy style "/content/admin", or # for a heading.'],
                    ['name' => 'icon', 'label' => 'Icon (Bootstrap Icons class)', 'type' => 'text', 'rules' => 'nullable|max:50'],
                    ['name' => 'ordering', 'label' => 'Ordering', 'type' => 'number', 'rules' => 'nullable|integer'],
                    ['name' => 'status', 'label' => 'Enabled', 'type' => 'bool', 'rules' => 'nullable|boolean'],
                ],
                'guard' => fn ($m) => Menu::where('parent_id', $m->id)->exists() ? 'It has child items.' : null,
            ],
            // ---------------------------------------------------------------- places
            'country' => [
                'acl' => 'Country', 'model' => Country::class, 'title' => 'Country', 'plural' => 'Countries', 'icon' => 'bi-globe',
                'order' => ['title', 'asc'], 'search' => ['title', 'country_2_code', 'country_3_code'],
                'columns' => [['id', 'ID'], ['title', 'Country'], ['country_2_code', 'Code 2'], ['country_3_code', 'Code 3'], ['status', 'Status', 'badge']],
                'fields' => [
                    ['name' => 'title', 'label' => 'Country', 'type' => 'text', 'rules' => 'required|max:255'],
                    ['name' => 'country_2_code', 'label' => 'Code 2', 'type' => 'text', 'rules' => 'nullable|max:2'],
                    ['name' => 'country_3_code', 'label' => 'Code 3', 'type' => 'text', 'rules' => 'nullable|max:3'],
                    $status,
                ],
                'guard' => fn ($m) => State::where('country_id', $m->id)->exists() ? 'It has states.' : null,
            ],
            'state' => [
                'acl' => 'State', 'model' => State::class, 'title' => 'State', 'plural' => 'States', 'icon' => 'bi-geo',
                'order' => ['title', 'asc'], 'search' => ['title'], 'filters' => ['country_id' => ['Country', 'countries']],
                'columns' => [['id', 'ID'], ['title', 'State'], ['country_id', 'Country', 'option', 'countries'], ['state_2_code', 'Code 2'], ['status', 'Status', 'badge']],
                'fields' => [
                    $geo('country_id', 'Country', 'countries'),
                    ['name' => 'title', 'label' => 'State', 'type' => 'text', 'rules' => 'required|max:192'],
                    ['name' => 'state_2_code', 'label' => 'Code 2', 'type' => 'text', 'rules' => 'nullable|max:6'],
                    ['name' => 'state_3_code', 'label' => 'Code 3', 'type' => 'text', 'rules' => 'nullable|max:9'],
                    $status,
                ],
                'guard' => fn ($m) => City::where('state_id', $m->id)->exists() ? 'It has cities.' : null,
            ],
            'city' => [
                'acl' => 'City', 'model' => City::class, 'title' => 'City', 'plural' => 'Cities', 'icon' => 'bi-building',
                'order' => ['title', 'asc'], 'search' => ['title'], 'filters' => ['state_id' => ['State', 'states']],
                'columns' => [['id', 'ID'], ['title', 'City'], ['state_id', 'State', 'option', 'states'], ['country_id', 'Country', 'option', 'countries'], ['status', 'Status', 'badge']],
                'fields' => [
                    $geo('country_id', 'Country', 'countries'), $geo('state_id', 'State', 'states'),
                    ['name' => 'title', 'label' => 'City', 'type' => 'text', 'rules' => 'required|max:255'],
                    ['name' => 'city_2_code', 'label' => 'Code 2', 'type' => 'text', 'rules' => 'nullable|max:6'],
                    ['name' => 'city_3_code', 'label' => 'Code 3', 'type' => 'text', 'rules' => 'nullable|max:9'],
                    $status,
                ],
                'guard' => fn ($m) => District::where('city_id', $m->id)->exists() ? 'It has districts.' : null,
            ],
            'district' => [
                'acl' => 'District', 'model' => District::class, 'title' => 'District', 'plural' => 'Districts', 'icon' => 'bi-pin-map',
                'order' => ['title', 'asc'], 'search' => ['title'], 'filters' => ['city_id' => ['City', 'cities']],
                'columns' => [['id', 'ID'], ['title', 'District'], ['city_id', 'City', 'option', 'cities'], ['state_id', 'State', 'option', 'states'], ['status', 'Status', 'badge']],
                'fields' => [
                    $geo('country_id', 'Country', 'countries'), $geo('state_id', 'State', 'states'), $geo('city_id', 'City', 'cities'),
                    ['name' => 'title', 'label' => 'District', 'type' => 'text', 'rules' => 'required|max:150'],
                    $status,
                ],
                'guard' => fn ($m) => Thana::where('district_id', $m->id)->exists() ? 'It has thanas.' : null,
            ],
            'thana' => [
                'acl' => 'Thana', 'model' => Thana::class, 'title' => 'Thana', 'plural' => 'Thanas', 'icon' => 'bi-shield',
                'order' => ['title', 'asc'], 'search' => ['title'], 'filters' => ['district_id' => ['District', 'districts']],
                'columns' => [['id', 'ID'], ['title', 'Thana'], ['district_id', 'District', 'option', 'districts'], ['city_id', 'City', 'option', 'cities'], ['status', 'Status', 'badge']],
                'fields' => [
                    $geo('country_id', 'Country', 'countries'), $geo('state_id', 'State', 'states'), $geo('city_id', 'City', 'cities'), $geo('district_id', 'District', 'districts'),
                    ['name' => 'title', 'label' => 'Thana', 'type' => 'text', 'rules' => 'required|max:100'],
                    $status,
                ],
            ],
            'customer' => [
                'acl' => 'customer', 'model' => Customer::class, 'title' => 'Customer', 'plural' => 'Customers', 'icon' => 'bi-person-badge',
                'order' => ['id', 'desc'], 'search' => ['full_name', 'username', 'email'],
                'columns' => [['id', 'ID'], ['full_name', 'Name'], ['username', 'Username'], ['email', 'Email'], ['status_id', 'Status', 'option', 'user_statuses']],
                'fields' => [
                    ['name' => 'full_name', 'label' => 'Full name', 'type' => 'text', 'rules' => 'required|max:150'],
                    ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'rules' => 'required|max:100|unique:customers,username,{id}'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => 'required|email|max:100|unique:customers,email,{id}'],
                    ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'rules' => 'nullable|min:6|max:100', 'required_on' => 'create', 'help' => 'Leave blank to keep the current password.'],
                    ['name' => 'status_id', 'label' => 'Status', 'type' => 'select', 'options' => 'user_statuses', 'rules' => 'required|integer'],
                ],
            ],
            // ---------------------------------------------------------------- logs (read-only)
            'audit-trail' => [
                'acl' => 'auditTrail', 'model' => AuditTrail::class, 'title' => 'Audit trail', 'plural' => 'Audit trail', 'icon' => 'bi-clock-history',
                'order' => ['id', 'desc'], 'search' => [], 'actions' => ['index', 'destroy'], 'filters' => ['user_id' => ['User', 'users']],
                'columns' => [['id', 'ID'], ['user_id', 'User', 'option', 'users'], ['login_time', 'Login', 'datetime'], ['logout_time', 'Logout', 'datetime']],
                'fields' => [],
            ],
            'visitor' => [
                'acl' => 'visitor', 'model' => Visitor::class, 'title' => 'Visitor statistics', 'plural' => 'Visitor statistics', 'icon' => 'bi-bar-chart',
                'order' => ['id', 'desc'], 'search' => ['user_name', 'page_link', 'visitor_ip'], 'actions' => ['index', 'destroy'],
                'columns' => [['id', 'ID'], ['user_name', 'User'], ['page_link', 'Page', 'limit', 60], ['visitor_ip', 'IP'], ['server_time', 'Time', 'datetime']],
                'fields' => [],
            ],
        ];

        foreach ($list as $slug => &$def) {
            $def['slug'] = $slug;
            $def['actions'] ??= ['index', 'create', 'edit', 'destroy'];
            $def['perPage'] ??= 25;
            $def['filters'] ??= [];
        }

        return $list;
    }
}
