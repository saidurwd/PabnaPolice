<?php

namespace App\Models;

/**
 * @property int $id
 * @property string|null $controller
 * @property string|null $title
 * @property int|null $controller_type
 * @property int|null $status
 */
class AclController extends BaseModel
{
    public $timestamps = false;
}
