<?php

namespace App\Models;

use App\Enums\GroupType;

/**
 * @property int $id
 * @property GroupType $group_type
 * @property string $title
 * @property string|null $details
 */
class UserGroup extends BaseModel
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['group_type' => GroupType::class];
    }
}
