<?php

namespace Tests\Feature\MediaItem;

use App\Enums\MediaType;
use App\Models\Genre;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_media_items_paginated(): void
    {
        $user = User::factory()->create();
        MediaItem::factory()->count(20)->create();

        $response = $this->actingAs($user)->getJson("/api/v1/media-items");

        $response->assertOk()
            ->assertJsonCount(15, "data")
            ->assertJsonStructure([
                "data" => [["id", "title", "type", "description", "cover_image", "release_date", "created_by", "created_at", "updated_at"]],
                "links",
                "meta" => ["current_page", "last_page", "per_page", "total"],
            ]);

        $this->assertSame(20, $response->json("meta.total"));
    }

    public function test_media_items_can_be_filtered_by_type(): void
    {
        $user = User::factory()->create();
        MediaItem::factory()->count(3)->create(["type" => MediaType::Game]);
        MediaItem::factory()->count(2)->create(["type" => MediaType::Film]);

        $response = $this->actingAs($user)->getJson("/api/v1/media-items?filter[type]=game");

        $response->assertOk()->assertJsonCount(3, "data");
        $this->assertSame(["game", "game", "game"], array_column($response->json("data"), "type"));
    }

    public function test_media_items_reject_invalid_type_filter(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/media-items?filter[type]=book")
            ->assertStatus(422)
            ->assertJsonValidationErrors(["filter.type"]);
    }

    public function test_media_items_can_be_sorted(): void
    {
        $user = User::factory()->create();
        MediaItem::factory()->create(["title" => "Zelda"]);
        MediaItem::factory()->create(["title" => "Alan Wake"]);

        $response = $this->actingAs($user)->getJson("/api/v1/media-items?sort=title");

        $response->assertOk();
        $this->assertSame(["Alan Wake", "Zelda"], array_column($response->json("data"), "title"));
    }

    public function test_media_items_can_include_genres(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $mediaItem = MediaItem::factory()->create();
        $mediaItem->genres()->attach($genre);

        $response = $this->actingAs($user)->getJson("/api/v1/media-items?include=genres");

        $response->assertOk()
            ->assertJsonPath("data.0.genres.0.id", $genre->id);
    }

    public function test_user_can_create_media_item_with_genres(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $response = $this->actingAs($user)->postJson("/api/v1/media-items", [
            "title" => "The Witcher 3",
            "type" => "game",
            "description" => "Open world RPG.",
            "release_date" => "2015-05-19",
            "genre_ids" => $genres->pluck("id")->all(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath("data.title", "The Witcher 3")
            ->assertJsonPath("data.type", "game")
            ->assertJsonPath("data.created_by", $user->id)
            ->assertJsonCount(2, "data.genres");

        $mediaItem = MediaItem::query()->where("title", "The Witcher 3")->first();
        $this->assertModelExists($mediaItem);
        $this->assertCount(2, $mediaItem->genres);
    }

    public function test_media_item_creation_requires_valid_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/media-items", ["type" => "book"])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["title", "type"]);
    }

    public function test_media_item_creation_rejects_unknown_genre_ids(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/media-items", [
                "title" => "Test",
                "type" => "film",
                "genre_ids" => ["00000000-0000-0000-0000-000000000000"],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["genre_ids.0"]);
    }

    public function test_user_can_view_media_item(): void
    {
        $user = User::factory()->create();
        $mediaItem = MediaItem::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/media-items/{$mediaItem->id}")
            ->assertOk()
            ->assertJsonPath("data.id", $mediaItem->id);
    }

    public function test_viewing_missing_media_item_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/media-items/00000000-0000-0000-0000-000000000000")
            ->assertNotFound();
    }

    public function test_creator_can_update_media_item(): void
    {
        $user = User::factory()->create();
        $mediaItem = MediaItem::factory()->create(["created_by" => $user->id]);
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->patchJson("/api/v1/media-items/{$mediaItem->id}", [
            "title" => "Updated Title",
            "genre_ids" => [$genre->id],
        ]);

        $response->assertOk()
            ->assertJsonPath("data.title", "Updated Title")
            ->assertJsonCount(1, "data.genres");

        $this->assertSame("Updated Title", $mediaItem->fresh()?->title);
    }

    public function test_non_creator_cannot_update_media_item(): void
    {
        $user = User::factory()->create();
        $mediaItem = MediaItem::factory()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/media-items/{$mediaItem->id}", ["title" => "Hijacked"])
            ->assertForbidden();
    }

    public function test_creator_can_delete_media_item(): void
    {
        $user = User::factory()->create();
        $mediaItem = MediaItem::factory()->create(["created_by" => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/media-items/{$mediaItem->id}")
            ->assertNoContent();

        $this->assertTrue($mediaItem->fresh()?->trashed());
    }

    public function test_non_creator_cannot_delete_media_item(): void
    {
        $user = User::factory()->create();
        $mediaItem = MediaItem::factory()->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/media-items/{$mediaItem->id}")
            ->assertForbidden();

        $this->assertFalse($mediaItem->fresh()?->trashed());
    }

    public function test_unauthenticated_user_cannot_access_media_items(): void
    {
        $this->getJson("/api/v1/media-items")->assertStatus(401);
        $this->postJson("/api/v1/media-items", [])->assertStatus(401);
    }
}
