<?php

namespace Tests\Feature\UserLibrary;

use App\Models\MediaItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_shows_only_own_reviewed_items(): void
    {
        $user = User::factory()->create();
        Review::factory()->count(2)->create(["user_id" => $user->id]);
        Review::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson("/api/v1/library");

        $response->assertOk()
            ->assertJsonCount(2, "data")
            ->assertJsonStructure([
                "data" => [["review_id", "rating", "comment", "has_spoiler", "media_item" => ["id", "title", "type", "genres"], "reviewed_at"]],
                "links",
                "meta",
            ]);
    }

    public function test_library_can_be_filtered_by_media_type(): void
    {
        $user = User::factory()->create();
        $game = MediaItem::factory()->create(["type" => "game"]);
        $series = MediaItem::factory()->create(["type" => "series"]);
        Review::factory()->create(["user_id" => $user->id, "media_item_id" => $game->id]);
        Review::factory()->create(["user_id" => $user->id, "media_item_id" => $series->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/library?filter[type]=series");

        $response->assertOk()
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.media_item.id", $series->id);
    }

    public function test_library_rejects_invalid_type_filter(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/library?filter[type]=music")
            ->assertStatus(422)
            ->assertJsonValidationErrors(["filter.type"]);
    }

    public function test_library_can_be_sorted_by_rating(): void
    {
        $user = User::factory()->create();
        Review::factory()->create(["user_id" => $user->id, "rating" => 3]);
        Review::factory()->create(["user_id" => $user->id, "rating" => 9]);

        $response = $this->actingAs($user)->getJson("/api/v1/library?sort=-rating");

        $response->assertOk();
        $this->assertSame([9, 3], array_column($response->json("data"), "rating"));
    }

    public function test_library_is_paginated(): void
    {
        $user = User::factory()->create();
        Review::factory()->count(20)->create(["user_id" => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/library");

        $response->assertOk()->assertJsonCount(15, "data");
        $this->assertSame(20, $response->json("meta.total"));
    }

    public function test_unauthenticated_user_cannot_access_library(): void
    {
        $this->getJson("/api/v1/library")->assertStatus(401);
    }
}
