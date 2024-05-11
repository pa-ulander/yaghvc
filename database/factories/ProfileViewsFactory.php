<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProfileViews;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfileViews>
 */
class ProfileViewsFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ProfileViews::class;

    /**
     * Holder for username
     *
     * @var string|null
     */
    protected static ?string $username;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->username(),
        ];
    }
}
