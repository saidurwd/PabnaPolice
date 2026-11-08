<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** Passwords are stored the way the live data has them (SHA1) unless the state says otherwise. */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(), 'username' => fake()->unique()->userName(), 'email' => fake()->unique()->safeEmail(),
            'password' => sha1('secret12'), 'photo' => '',
            'status_id' => fn () => self::ensureStatus(1),
        ];
    }

    /** Gives the user the named role (created if missing). Roles are what allow someone into the admin panel. */
    public function role(string $name): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Role::findOrCreate($name, 'web')));
    }

    public function superUser(): static
    {
        return $this->role(config('admin.super_role'));
    }

    /** The foreign key needs a real row: create the status if the test database does not have it yet. */
    private static function ensureStatus(int $id): int
    {
        DB::table('user_statuses')->insertOrIgnore(['id' => $id, 'title' => 'Active']);

        return $id;
    }
}
