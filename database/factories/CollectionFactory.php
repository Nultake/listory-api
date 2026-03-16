<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => fake()->words(3, true),
            "description" => fake()->optional()->paragraph(),
            "is_public" => fake()->boolean(30),
            "user_id" => User::factory(),
        ];
    }
}
