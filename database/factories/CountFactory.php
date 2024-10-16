<?php

namespace Database\Factories;

use App\Models\Count;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Count>
 */
class CountFactory extends Factory
{
    protected $model = Count::class;

    public function definition(): array
    {
        return [
            'count' => $this->faker->numberBetween(int1: 1, int2: 1000),
        ];
    }
}
