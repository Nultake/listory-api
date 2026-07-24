<?php

namespace Tests\Feature\Collection;

use App\Enums\CollectionRole;
use App\Models\Collection;
use App\Models\CollectionInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_list_collection_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);

        $response = $this->actingAs($member)->getJson("/api/v1/collections/{$collection->id}/members");

        $response->assertOk()->assertJsonCount(2, "data");
        $roles = array_column($response->json("data"), "role", "id");
        $this->assertSame(CollectionRole::Owner->value, $roles[$owner->id]);
        $this->assertSame(CollectionRole::Member->value, $roles[$member->id]);
    }

    public function test_non_member_cannot_list_members_of_private_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(["is_public" => false]);

        $this->actingAs($user)
            ->getJson("/api/v1/collections/{$collection->id}/members")
            ->assertForbidden();
    }

    public function test_owner_can_remove_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/collections/{$collection->id}/members/{$member->id}")
            ->assertNoContent();

        $this->assertFalse($collection->hasMember($member));
    }

    public function test_member_can_leave_collection(): void
    {
        $member = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);

        $this->actingAs($member)
            ->deleteJson("/api/v1/collections/{$collection->id}/members/{$member->id}")
            ->assertNoContent();

        $this->assertFalse($collection->hasMember($member));
    }

    public function test_member_cannot_remove_another_member(): void
    {
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->members()->attach($memberA->id, ["role" => CollectionRole::Member->value]);
        $collection->members()->attach($memberB->id, ["role" => CollectionRole::Member->value]);

        $this->actingAs($memberA)
            ->deleteJson("/api/v1/collections/{$collection->id}/members/{$memberB->id}")
            ->assertForbidden();

        $this->assertTrue($collection->hasMember($memberB));
    }

    public function test_owner_cannot_be_removed(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);

        $this->actingAs($member)
            ->deleteJson("/api/v1/collections/{$collection->id}/members/{$owner->id}")
            ->assertForbidden();

        $this->actingAs($owner)
            ->deleteJson("/api/v1/collections/{$collection->id}/members/{$owner->id}")
            ->assertForbidden();
    }

    public function test_removing_member_deletes_their_invitation(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);
        $invitation = CollectionInvitation::factory()->create([
            "collection_id" => $collection->id,
            "inviter_id" => $owner->id,
            "invitee_id" => $member->id,
            "status" => "accepted",
        ]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/collections/{$collection->id}/members/{$member->id}")
            ->assertNoContent();

        $this->assertModelMissing($invitation);
    }

    public function test_removing_non_member_returns_404(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/collections/{$collection->id}/members/{$stranger->id}")
            ->assertNotFound();
    }
}
