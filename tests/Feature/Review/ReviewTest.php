<?php

namespace Tests\Feature\Review;

use App\Models\MediaItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_review(): void
    {
        $user = User::factory()->create();
        $mediaItem = MediaItem::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/reviews", [
            "media_item_id" => $mediaItem->id,
            "rating" => 9,
            "comment" => "Masterpiece.",
            "has_spoiler" => false,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath("data.rating", 9)
            ->assertJsonPath("data.comment", "Masterpiece.")
            ->assertJsonPath("data.user_id", $user->id)
            ->assertJsonPath("data.media_item.id", $mediaItem->id);

        $review = Review::query()->where("user_id", $user->id)->where("media_item_id", $mediaItem->id)->first();
        $this->assertModelExists($review);
    }

    public function test_user_cannot_review_same_media_item_twice(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(["user_id" => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/reviews", [
                "media_item_id" => $review->media_item_id,
                "rating" => 5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["media_item_id"]);
    }

    public function test_different_users_can_review_same_media_item(): void
    {
        $mediaItem = MediaItem::factory()->create();
        Review::factory()->create(["media_item_id" => $mediaItem->id]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/reviews", [
                "media_item_id" => $mediaItem->id,
                "rating" => 7,
            ])
            ->assertStatus(201);
    }

    public function test_review_requires_rating_between_1_and_10(): void
    {
        $user = User::factory()->create();
        $mediaItem = MediaItem::factory()->create();

        foreach ([0, 11] as $rating) {
            $this->actingAs($user)
                ->postJson("/api/v1/reviews", [
                    "media_item_id" => $mediaItem->id,
                    "rating" => $rating,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(["rating"]);
        }
    }

    public function test_review_requires_existing_media_item(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/reviews", [
                "media_item_id" => "00000000-0000-0000-0000-000000000000",
                "rating" => 5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["media_item_id"]);
    }

    public function test_user_can_list_own_reviews_only(): void
    {
        $user = User::factory()->create();
        Review::factory()->count(3)->create(["user_id" => $user->id]);
        Review::factory()->count(2)->create();

        $response = $this->actingAs($user)->getJson("/api/v1/reviews");

        $response->assertOk()->assertJsonCount(3, "data");
        $this->assertSame([$user->id, $user->id, $user->id], array_column($response->json("data"), "user_id"));
    }

    public function test_reviews_can_be_filtered_by_media_type(): void
    {
        $user = User::factory()->create();
        $game = MediaItem::factory()->create(["type" => "game"]);
        $film = MediaItem::factory()->create(["type" => "film"]);
        Review::factory()->create(["user_id" => $user->id, "media_item_id" => $game->id]);
        Review::factory()->create(["user_id" => $user->id, "media_item_id" => $film->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/reviews?filter[type]=game");

        $response->assertOk()
            ->assertJsonCount(1, "data")
            ->assertJsonPath("data.0.media_item_id", $game->id);
    }

    public function test_user_can_view_any_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/reviews/{$review->id}")
            ->assertOk()
            ->assertJsonPath("data.id", $review->id);
    }

    public function test_owner_can_update_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(["user_id" => $user->id, "rating" => 4]);

        $this->actingAs($user)
            ->patchJson("/api/v1/reviews/{$review->id}", ["rating" => 8, "has_spoiler" => true])
            ->assertOk()
            ->assertJsonPath("data.rating", 8)
            ->assertJsonPath("data.has_spoiler", true);

        $this->assertSame(8, $review->fresh()?->rating);
    }

    public function test_non_owner_cannot_update_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/reviews/{$review->id}", ["rating" => 1])
            ->assertForbidden();
    }

    public function test_owner_can_delete_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(["user_id" => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/reviews/{$review->id}")
            ->assertNoContent();

        $this->assertTrue($review->fresh()?->trashed());
    }

    public function test_non_owner_cannot_delete_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/reviews/{$review->id}")
            ->assertForbidden();
    }

    public function test_user_can_review_again_after_deleting_own_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(["user_id" => $user->id, "rating" => 3]);

        $this->actingAs($user)->deleteJson("/api/v1/reviews/{$review->id}")->assertNoContent();

        $response = $this->actingAs($user)->postJson("/api/v1/reviews", [
            "media_item_id" => $review->media_item_id,
            "rating" => 10,
            "comment" => "Changed my mind.",
        ]);

        $response->assertStatus(201)
            ->assertJsonPath("data.id", $review->id)
            ->assertJsonPath("data.rating", 10);

        $fresh = $review->fresh();
        $this->assertFalse($fresh?->trashed());
        $this->assertSame(10, $fresh?->rating);
    }

    public function test_unauthenticated_user_cannot_access_reviews(): void
    {
        $this->getJson("/api/v1/reviews")->assertStatus(401);
        $this->postJson("/api/v1/reviews", [])->assertStatus(401);
    }
}
