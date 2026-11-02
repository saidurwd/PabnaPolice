<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\YesNo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $title
 * @property string|null $banner
 * @property string|null $clickurl
 * @property string|null $details
 * @property Status|null $status
 * @property YesNo|null $sticky
 * @property int|null $ordering
 * @property int|null $created_by
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Banner extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'sticky' => YesNo::class,
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<BannerCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BannerCategory::class);
    }

    /** @param  Builder<static>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', Status::Active->value);
    }

    public function imageUrl(): string
    {
        return upload_url('banner', $this->banner) ?? asset('uploads/banner/default.jpg');
    }
}
