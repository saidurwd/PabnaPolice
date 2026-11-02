<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\BannerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BannerCategory> */
class BannerCategoryFactory extends Factory
{
    protected $model = BannerCategory::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return ['title' => $title, 'alias' => $title, 'status' => Status::Active, 'created_by' => 1];
    }
}
