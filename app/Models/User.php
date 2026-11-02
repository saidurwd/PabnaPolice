<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * Back-office user.
 *
 * @property int $id
 * @property string $full_name
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $activation
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
 * @property-read string|null $role  name of the user's (first) role
 */
class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

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

    /** @return BelongsTo<UserStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(UserStatus::class);
    }

    /** The super role (config admin.super_role) may do everything; Gate::before in AppServiceProvider honours it. */
    public function isSuperUser(): bool
    {
        return $this->hasRole(config('admin.super_role'));
    }

    /** True when this is the only remaining super user (so it must not be deleted or demoted). */
    public function isLastSuperUser(): bool
    {
        return $this->isSuperUser() && User::role(config('admin.super_role'))->count() <= 1;
    }

    /** Name of the user's role, for the admin forms and lists (an old-style accessor: a role() method would shadow Spatie's scope). */
    public function getRoleAttribute(): ?string
    {
        return $this->roles->pluck('name')->first();
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
