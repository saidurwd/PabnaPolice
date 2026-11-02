<?php

namespace App\Models;

/**
 * @property int $id
 * @property int|null $controller_id
 * @property string|null $title
 * @property string|null $action
 * @property int|null $controller_type
 */
class AclAction extends BaseModel
{
    public $timestamps = false;
}
