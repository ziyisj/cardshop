<?php

namespace Database\Factories;

use App\Models\CardKey;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardKeyFactory extends Factory
{
    protected $model = CardKey::class;

    public function definition(): array
    {
        return [
            'code'          => CardKey::generateCode(),
            'product_id'    => Product::factory(),
            'duration_days' => 30,
            'status'        => 'unused',
        ];
    }

    public function used(): static
    {
        return $this->state(fn () => ['status' => 'used', 'used_at' => now()]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => 'disabled']);
    }
}
