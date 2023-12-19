<?php

namespace Database\Factories;

use App\Models\GlobalField;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'product_id' => Product::factory()->create()->id,
            'default_product_id' => null,
            'option_1_global_field_id' => GlobalField::factory()->create()->id,
            'option_1_value' => fake()->word(),
            'option_2_global_field_id' => GlobalField::factory()->create()->id,
            'option_2_value' => fake()->word(),
            'option_3_global_field_id' => GlobalField::factory()->create()->id,
            'option_3_value' => fake()->word(),
            'sku' => fake()->unique()->slug(3),
            'gtin' => fake()->unique()->ean13(),
            'stock_quantity' => rand(0,1000),
            'price' => rand(100,10000),
            'compare_at_price' => rand(100,10000) > 5000? rand(100,10000):null
        ];
    }
}
