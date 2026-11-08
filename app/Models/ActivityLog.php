<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One row of the Audit log (log = "audit": a record was created / changed / deleted, with old and new values)
 * or of the Activity log (log = "activity": something a signed-in user did).
 *
 * @property int $id
 * @property string $log
 * @property string $event
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $subject_label
 * @property string $description
 * @property array|null $properties
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 */
class ActivityLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['properties' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
