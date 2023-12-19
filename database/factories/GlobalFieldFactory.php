<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GlobalField>
 */
class GlobalFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $mutable = rand(0,1) > 0;
        return [
            'brand_id' => Brand::factory()->create()->id,
            'label' => fake()->randomElement([
                'Color',
                'Size',
                'Material',
                'Style',
                'Pattern',
                'Sleeve Length',
                'Neckline',
                'Dress Length',
                'Bottoms Length',
                'Top Length',
                'Waist Line',
                'Silhouette',
                'Decoration',
                'Fabric',
                'Fit Type',
                'Lining',
                'Belt',
            ]),
            'shop_reference' => $mutable? fake()->slug(3):null,
            'default_value' => null,
            'mutable' => $mutable
        ];
    }
}
