<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

/**
 * @property int $id
 * @property string $title
 */
class UserStatus extends BaseModel
{
    use Auditable;

    public $timestamps = false;
}
