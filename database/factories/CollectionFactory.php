<?php

namespace Database\Factories;

use App\Enums\CollectionRole;
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

    /**
     * Register the owner as the collection's first member after creation,
     * mirroring CreateCollectionAction.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Collection $collection): void {
            $collection->members()->syncWithoutDetaching([
                $collection->user_id => ["role" => CollectionRole::Owner->value],
            ]);
        });
    }
}
