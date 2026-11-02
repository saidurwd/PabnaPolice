<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class BannerCategory extends CategoryNode
{
    /** @return HasMany<Banner, $this> */
    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }
}
