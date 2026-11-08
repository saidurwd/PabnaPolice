<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentCategory> */
class DocumentCategoryFactory extends Factory
{
    protected $model = DocumentCategory::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return ['title' => $title, 'alias' => $title, 'status' => Status::Active, 'created_by' => 1];
    }
}
