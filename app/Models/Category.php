<?php

namespace App\Models;

use App\Enums\YesNo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property YesNo|null $listing
 */
class Category extends CategoryNode
{
    protected function casts(): array
    {
        return parent::casts() + ['listing' => YesNo::class];
    }

    /** @return HasMany<Content, $this> */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }
}
