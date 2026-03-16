<?php

namespace Database\Factories;

use App\Models\MediaItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "user_id" => User::factory(),
            "media_item_id" => MediaItem::factory(),
            "rating" => fake()->numberBetween(1, 10),
            "comment" => fake()->optional()->paragraph(),
            "has_spoiler" => fake()->boolean(20),
        ];
    }
}
