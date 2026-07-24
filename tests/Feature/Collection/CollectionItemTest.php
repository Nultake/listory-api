<?php

namespace Tests\Feature\Collection;

use App\Enums\CollectionRole;
use App\Models\Collection;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_item_to_collection(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        $mediaItem = MediaItem::factory()->create();

        $this->actingAs($owner)
            ->postJson("/api/v1/collections/{$collection->id}/items", [
                "media_item_id" => $mediaItem->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath("data.id", $mediaItem->id);

        $this->assertTrue($collection->mediaItems()->whereKey($mediaItem->id)->exists());
    }

    public function test_member_can_add_item_to_collection(): void
    {
        $member = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);
        $mediaItem = MediaItem::factory()->create();

        $this->actingAs($member)
            ->postJson("/api/v1/collections/{$collection->id}/items", [
                "media_item_id" => $mediaItem->id,
            ])
            ->assertStatus(201);
    }

    public function test_non_member_cannot_add_item_to_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();
        $mediaItem = MediaItem::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/collections/{$collection->id}/items", [
                "media_item_id" => $mediaItem->id,
            ])
            ->assertForbidden();
    }

    public function test_cannot_add_same_item_twice(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        $mediaItem = MediaItem::factory()->create();
        $collection->mediaItems()->attach($mediaItem->id);

        $this->actingAs($owner)
            ->postJson("/api/v1/collections/{$collection->id}/items", [
                "media_item_id" => $mediaItem->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["media_item_id"]);
    }

    public function test_cannot_add_nonexistent_item(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/api/v1/collections/{$collection->id}/items", [
                "media_item_id" => "00000000-0000-0000-0000-000000000000",
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["media_item_id"]);
    }

    public function test_member_can_remove_item_from_collection(): void
    {
        $member = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);
        $mediaItem = MediaItem::factory()->create();
        $collection->mediaItems()->attach($mediaItem->id);

        $this->actingAs($member)
            ->deleteJson("/api/v1/collections/{$collection->id}/items/{$mediaItem->id}")
            ->assertNoContent();

        $this->assertFalse($collection->mediaItems()->whereKey($mediaItem->id)->exists());
    }

    public function test_removing_item_not_in_collection_returns_404(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        $mediaItem = MediaItem::factory()->create();

        $this->actingAs($owner)
            ->deleteJson("/api/v1/collections/{$collection->id}/items/{$mediaItem->id}")
            ->assertNotFound();
    }

    public function test_non_member_cannot_remove_item_from_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();
        $mediaItem = MediaItem::factory()->create();
        $collection->mediaItems()->attach($mediaItem->id);

        $this->actingAs($user)
            ->deleteJson("/api/v1/collections/{$collection->id}/items/{$mediaItem->id}")
            ->assertForbidden();
    }
}
