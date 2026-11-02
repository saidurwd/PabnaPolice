<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends CategoryNode
{
    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
