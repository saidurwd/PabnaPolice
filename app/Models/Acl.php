<?php

namespace App\Models;

/**
 * One permission: user group x module x action.
 *
 * @property int $id
 * @property int|null $group_id
 * @property string|null $controller
 * @property string|null $actions
 * @property string|null $action_title
 * @property int|null $access
 * @property int|null $controller_type
 */
class Acl extends BaseModel
{
    protected $table = 'acl_rules';

    public $timestamps = false;
}
