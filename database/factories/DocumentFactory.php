<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return ['category_id' => DocumentCategory::factory(), 'title' => fake()->sentence(3), 'document_file' => 'missing.pdf', 'status' => Status::Active, 'created_by' => 1, 'published_at' => now()];
    }
}
