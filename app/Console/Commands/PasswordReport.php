<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PasswordReport extends Command
{
    protected $signature = 'users:password-report';

    protected $description = 'Show how many users still have a legacy SHA1 password and how many use two-factor authentication';

    public function handle(): int
    {
        $users = User::orderBy('id')->get(['id', 'username', 'password', 'two_factor_confirmed_at', 'last_login_at']);

        $rows = $users->map(fn (User $u) => [
            $u->id,
            $u->username,
            str_starts_with($u->password, '$2y$') ? 'bcrypt' : 'legacy SHA1',
            $u->hasTwoFactorEnabled() ? 'yes' : 'no',
        ])->all();

        $this->table(['ID', 'Username', 'Password hash', '2FA'], $rows);

        $legacy = $users->filter(fn (User $u) => ! str_starts_with($u->password, '$2y$'))->count();
        $this->line("{$legacy} of {$users->count()} users still have a legacy SHA1 password.");
        $this->line(config('legacy.password_upgrade')
            ? 'LEGACY_PASSWORD_UPGRADE is ON: those users are upgraded to bcrypt the next time they sign in or reset their password.'
            : 'LEGACY_PASSWORD_UPGRADE is OFF: set it to true after the old Yii admin is retired.');

        return self::SUCCESS;
    }
}
