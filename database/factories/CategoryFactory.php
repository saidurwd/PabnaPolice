<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Enums\YesNo;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return ['title' => $title, 'alias' => $title, 'listing' => YesNo::No, 'status' => Status::Active, 'created_by' => 1];
    }
}
