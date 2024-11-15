<?php

namespace Database\Factories;

use App\Models\Count;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Count>
 */
class CountFactory extends Factory
{
    protected $model = Count::class;

    public function definition(): array
    {
        return [
            'count' => $this->faker->numberBetween(1, 1000),
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Count $count) {
            // This is empty as we don't need to do anything after making
        })->afterCreating(function (Count $count) {
            // This is empty as we don't need to do anything after creating
        });
    }

    public function create($attributes = [], ?Model $parent = null)
    {
        $count = $this->definition()['count'];
        return new Count($count);
    }
}