<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

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
            'group_id' => fn () => self::ensureGroup(1),
            'status_id' => fn () => self::ensureStatus(1),
        ];
    }

    public function inGroup(int $group): static
    {
        return $this->state(['group_id' => self::ensureGroup($group)]);
    }

    /** The foreign keys need real rows: create the group/status if the test database does not have them yet. */
    private static function ensureGroup(int $id): int
    {
        DB::table('user_groups')->insertOrIgnore(['id' => $id, 'group_type' => 'Back', 'title' => $id === 1 ? 'Super Users' : "Group {$id}"]);

        return $id;
    }

    private static function ensureStatus(int $id): int
    {
        DB::table('user_statuses')->insertOrIgnore(['id' => $id, 'title' => 'Active']);

        return $id;
    }
}
