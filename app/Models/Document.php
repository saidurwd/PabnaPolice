<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $title
 * @property string|null $document_file
 * @property string|null $document_type
 * @property string|null $document_size
 * @property string|null $details
 * @property Status|null $status
 * @property int|null $created_by
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Document extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DocumentCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class);
    }

    /** @param  Builder<static>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', Status::Active->value);
    }

    /** Path of the stored file on the `uploads` disk. */
    public function storagePath(): string
    {
        return 'document/'.$this->document_file;
    }
}
