<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Enums\YesNo;
use App\Models\Banner;
use App\Models\BannerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Banner> */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'category_id' => BannerCategory::factory(), 'title' => fake()->words(3, true), 'banner' => 'sample.jpg',
            'sticky' => YesNo::No, 'status' => Status::Active, 'ordering' => 0, 'created_by' => 1, 'published_at' => now(),
        ];
    }
}
