<?php

namespace Tests\Feature\Collection;

use App\Enums\CollectionRole;
use App\Models\Collection;
use App\Models\MediaItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_collection(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/collections", [
            "name" => "Co-op Games 2026",
            "description" => "Games we played together.",
            "is_public" => false,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath("data.name", "Co-op Games 2026")
            ->assertJsonPath("data.owner_id", $user->id)
            ->assertJsonPath("data.members_count", 1);

        $collection = Collection::query()->where("user_id", $user->id)->first();
        $this->assertModelExists($collection);
        $this->assertTrue($collection?->hasMember($user));
        $this->assertSame(
            CollectionRole::Owner->value,
            $collection?->members()->whereKey($user->id)->first()?->getRelationValue("pivot")?->getAttribute("role"),
        );
    }

    public function test_collection_requires_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/collections", ["description" => "No name."])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["name"]);
    }

    public function test_user_can_list_only_collections_they_belong_to(): void
    {
        $user = User::factory()->create();
        $owned = Collection::factory()->create(["user_id" => $user->id]);
        $joined = Collection::factory()->create();
        $joined->members()->attach($user->id, ["role" => CollectionRole::Member->value]);
        Collection::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/v1/collections");

        $response->assertOk()->assertJsonCount(2, "data");
        $ids = array_column($response->json("data"), "id");
        $this->assertEqualsCanonicalizing([$owned->id, $joined->id], $ids);
    }

    public function test_owner_can_update_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $user->id]);

        $this->actingAs($user)
            ->patchJson("/api/v1/collections/{$collection->id}", [
                "name" => "Renamed",
                "is_public" => true,
            ])
            ->assertOk()
            ->assertJsonPath("data.name", "Renamed")
            ->assertJsonPath("data.is_public", true);

        $this->assertSame("Renamed", $collection->fresh()?->name);
    }

    public function test_member_cannot_update_collection(): void
    {
        $member = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);

        $this->actingAs($member)
            ->patchJson("/api/v1/collections/{$collection->id}", ["name" => "Hijacked"])
            ->assertForbidden();
    }

    public function test_owner_can_delete_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/collections/{$collection->id}")
            ->assertNoContent();

        $this->assertModelMissing($collection);
    }

    public function test_member_cannot_delete_collection(): void
    {
        $member = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);

        $this->actingAs($member)
            ->deleteJson("/api/v1/collections/{$collection->id}")
            ->assertForbidden();

        $this->assertModelExists($collection);
    }

    public function test_detail_view_shows_all_members_reviews_for_items(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);

        $mediaItem = MediaItem::factory()->create();
        $collection->mediaItems()->attach($mediaItem->id);

        Review::factory()->create(["user_id" => $owner->id, "media_item_id" => $mediaItem->id, "rating" => 8]);
        Review::factory()->create(["user_id" => $member->id, "media_item_id" => $mediaItem->id, "rating" => 6]);
        Review::factory()->create(["user_id" => $outsider->id, "media_item_id" => $mediaItem->id, "rating" => 2]);

        $response = $this->actingAs($member)->getJson("/api/v1/collections/{$collection->id}");

        $response->assertOk()
            ->assertJsonPath("data.id", $collection->id)
            ->assertJsonCount(2, "data.members")
            ->assertJsonCount(1, "data.media_items")
            ->assertJsonCount(2, "data.media_items.0.reviews");

        $reviewerIds = array_column($response->json("data.media_items.0.reviews"), "user_id");
        $this->assertEqualsCanonicalizing([$owner->id, $member->id], $reviewerIds);
    }

    public function test_non_member_cannot_view_private_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(["is_public" => false]);

        $this->actingAs($user)
            ->getJson("/api/v1/collections/{$collection->id}")
            ->assertForbidden();
    }

    public function test_non_member_can_view_public_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(["is_public" => true]);

        $this->actingAs($user)
            ->getJson("/api/v1/collections/{$collection->id}")
            ->assertOk()
            ->assertJsonPath("data.id", $collection->id);
    }

    public function test_unauthenticated_user_cannot_access_collections(): void
    {
        $this->getJson("/api/v1/collections")->assertStatus(401);
        $this->postJson("/api/v1/collections", [])->assertStatus(401);
    }
}
