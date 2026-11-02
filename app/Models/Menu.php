<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $title
 * @property string|null $controller
 * @property string $url
 * @property string|null $icon
 * @property int|null $ordering
 * @property int|null $status
 * @property string|null $group
 */
class Menu extends BaseModel
{
    public $timestamps = false;

    /** @return HasMany<Menu, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordering');
    }
}
