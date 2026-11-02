<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Back-office user.
 *
 * @property int $id
 * @property string $full_name
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $activation
 * @property int|null $group_id
 * @property int|null $status_id
 * @property string|null $picture
 * @property string|null $photo
 * @property string|null $remember_token
 * @property string|null $two_factor_secret
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read UserGroup|null $group
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<UserGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    /** @return BelongsTo<UserStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(UserStatus::class);
    }

    /** Super Users (group 1) bypass the ACL table. */
    public function isSuperUser(): bool
    {
        return (int) $this->group_id === 1;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function photoUrl(): ?string
    {
        return upload_url('user', $this->photo);
    }
}
