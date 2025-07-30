<?php

namespace Database\Factories;

use App\Models\ProfileViews;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'repository' => null, // Default to profile view (no repository)
        ];
    }

    /**
     * Create a repository-specific profile view
     */
    public function forRepository(string $repository): static
    {
        return $this->state(fn(array $attributes) => [
            'repository' => $repository,
        ]);
    }
}
