<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Shared shape of the three self-referencing category tables (content, banner and document
 * categories): `parent_id` points at another row, `path` is "0.1.5" and `alias` is "Parent/Child".
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $title
 * @property string|null $alias
 * @property string|null $details
 * @property string|null $path
 * @property Status $status
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
abstract class CategoryNode extends BaseModel
{
    protected function casts(): array
    {
        return ['status' => Status::class];
    }

    /** @return BelongsTo<static, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /** @return HasMany<static, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /** @param  Builder<static>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', Status::Active->value);
    }

    /** Recompute `path` and `alias` for this node and everything below it (after a rename or move). */
    public function rebuildTree(int $depth = 0): void
    {
        $parent = $this->parent_id ? static::find($this->parent_id) : null;
        $path = ($parent ? $parent->path : '0').'.'.$this->id;
        $alias = $parent ? $parent->alias.'/'.$this->title : $this->title;

        if ($this->path !== $path || $this->alias !== $alias) {
            $this->path = $path;
            $this->alias = $alias;
            $this->save();
        }

        if ($depth > 10) {
            return;
        }

        foreach (static::where('parent_id', $this->id)->get() as $child) {
            $child->rebuildTree($depth + 1);
        }
    }
}
