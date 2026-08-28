<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        return [
            'name'          => $name,
            'slug'          => Str::slug($name) . '-' . Str::random(4),
            'description'   => $this->faker->sentence(),
            'price'         => $this->faker->randomFloat(2, 5, 200),
            'duration_days' => $this->faker->randomElement([30, 90, 365]),
            'is_active'     => true,
            'sort'          => 0,
        ];
    }
}
