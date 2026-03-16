<?php

namespace Database\Factories;

use App\Enums\MediaType;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaItem>
 */
class MediaItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "title" => fake()->sentence(3),
            "type" => fake()->randomElement(MediaType::cases()),
            "description" => fake()->paragraph(),
            "cover_image" => fake()->imageUrl(),
            "release_date" => fake()->date(),
            "created_by" => User::factory(),
        ];
    }
}
