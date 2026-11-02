<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $login_time
 * @property string|null $logout_time
 */
class AuditTrail extends BaseModel
{
    public $timestamps = false;

    /** The user may have been deleted since (there is deliberately no foreign key). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
