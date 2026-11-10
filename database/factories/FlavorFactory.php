<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\TobaccoType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flavor>
 */
class FlavorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'name' => 'Sabor '.fake()->unique()->numberBetween(1, 9999999),
            'description' => null,
            // Spec 012: "Rubio" lo crea la migración de los tipos de tabaco en cada base de datos.
            'tobacco_type_id' => fn () => TobaccoType::where('name', 'Rubio')->value('id'),
            'ingredients_text' => null,
            'image_url' => null,
            'is_public' => true,
        ];
    }

    public function notPublic(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
