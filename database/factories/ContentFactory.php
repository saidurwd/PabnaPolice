<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Enums\YesNo;
use App\Models\Category;
use App\Models\Content;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Content> */
class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'details' => '<p>'.fake()->paragraph().'</p>',
            'category_id' => Category::factory(),
            'featured' => YesNo::No,
            'ordering' => 0,
            'status' => Status::Active,
            'hits' => 0,
            'created_by' => 1,
            'published_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => Status::Inactive]);
    }
}
