<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\YesNo;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $details
 * @property int $category_id
 * @property string|null $image
 * @property YesNo|null $featured
 * @property int|null $ordering
 * @property Status|null $status
 * @property string|null $metakey
 * @property string|null $metadesc
 * @property int|null $hits
 * @property int $created_by
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Content extends BaseModel
{
    use Auditable;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'featured' => YesNo::class,
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @param  Builder<static>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', Status::Active->value);
    }

    /** <img> like Content::get_picture() in Yii: falls back to uploads/content/default.jpg. */
    public function pictureHtml(string $class = 'img-responsive'): string
    {
        $src = $this->imageUrl() ?? asset('uploads/content/default.jpg');

        return '<img src="'.e($src).'" alt="'.e($this->title).'" class="'.e($class).'" title="'.e($this->title).'">';
    }

    /** Public URL of the picture, or null when the file is missing. */
    public function imageUrl(): ?string
    {
        return upload_url('content', $this->image);
    }
}
