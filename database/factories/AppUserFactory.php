<?php

namespace Database\Factories;

use App\Models\AppUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AppUserFactory extends Factory
{
    protected $model = AppUser::class;

    public function definition(): array
    {
        return [
            'username'    => $this->faker->unique()->userName(),
            'password'    => Hash::make('password'),
            'email'       => $this->faker->safeEmail(),
            'expires_at'  => now()->addDays(30),
            'is_banned'   => false,
            'max_devices' => 1,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function banned(): static
    {
        return $this->state(fn () => ['is_banned' => true]);
    }
}
