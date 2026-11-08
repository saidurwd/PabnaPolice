<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $type
 * @property string $severity info|warning|critical
 * @property int|null $user_id
 * @property string|null $identifier
 * @property string $message
 * @property array|null $details
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 */
class SecurityEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
