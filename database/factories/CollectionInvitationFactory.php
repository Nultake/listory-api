<?php

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Models\Collection;
use App\Models\CollectionInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionInvitation>
 */
class CollectionInvitationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "collection_id" => Collection::factory(),
            "inviter_id" => User::factory(),
            "invitee_id" => User::factory(),
            "status" => InvitationStatus::Pending,
            "message" => fake()->optional()->sentence(),
        ];
    }
}
