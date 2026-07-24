<?php

namespace Tests\Feature\Collection;

use App\Enums\CollectionRole;
use App\Enums\InvitationStatus;
use App\Models\Collection;
use App\Models\CollectionInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_user_by_email(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);

        $response = $this->actingAs($owner)->postJson("/api/v1/collections/{$collection->id}/invitations", [
            "email" => $invitee->email,
            "message" => "Join us!",
        ]);

        $response->assertStatus(201)
            ->assertJsonPath("data.invitee_id", $invitee->id)
            ->assertJsonPath("data.status", InvitationStatus::Pending->value)
            ->assertJsonPath("data.message", "Join us!");

        $invitation = CollectionInvitation::query()
            ->where("collection_id", $collection->id)
            ->where("invitee_id", $invitee->id)
            ->first();
        $this->assertModelExists($invitation);
    }

    public function test_member_cannot_invite(): void
    {
        $member = User::factory()->create();
        $invitee = User::factory()->create();
        $collection = Collection::factory()->create();
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);

        $this->actingAs($member)
            ->postJson("/api/v1/collections/{$collection->id}/invitations", [
                "email" => $invitee->email,
            ])
            ->assertForbidden();
    }

    public function test_cannot_invite_existing_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);

        $this->actingAs($owner)
            ->postJson("/api/v1/collections/{$collection->id}/invitations", [
                "email" => $member->email,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["email"]);
    }

    public function test_owner_cannot_invite_themselves(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/api/v1/collections/{$collection->id}/invitations", [
                "email" => $owner->email,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["email"]);
    }

    public function test_cannot_invite_unknown_email(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/api/v1/collections/{$collection->id}/invitations", [
                "email" => "nobody@example.com",
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["email"]);
    }

    public function test_cannot_send_duplicate_pending_invitation(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        CollectionInvitation::factory()->create([
            "collection_id" => $collection->id,
            "inviter_id" => $owner->id,
            "invitee_id" => $invitee->id,
            "status" => InvitationStatus::Pending,
        ]);

        $this->actingAs($owner)
            ->postJson("/api/v1/collections/{$collection->id}/invitations", [
                "email" => $invitee->email,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["email"]);
    }

    public function test_declined_invitation_is_reopened_when_reinvited(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $collection = Collection::factory()->create(["user_id" => $owner->id]);
        $invitation = CollectionInvitation::factory()->create([
            "collection_id" => $collection->id,
            "inviter_id" => $owner->id,
            "invitee_id" => $invitee->id,
            "status" => InvitationStatus::Declined,
        ]);

        $this->actingAs($owner)
            ->postJson("/api/v1/collections/{$collection->id}/invitations", [
                "email" => $invitee->email,
                "message" => "Please reconsider.",
            ])
            ->assertStatus(201)
            ->assertJsonPath("data.id", $invitation->id)
            ->assertJsonPath("data.status", InvitationStatus::Pending->value);

        $this->assertSame(InvitationStatus::Pending, $invitation->fresh()?->status);
    }

    public function test_invitee_can_list_received_invitations(): void
    {
        $invitee = User::factory()->create();
        CollectionInvitation::factory()->count(2)->create(["invitee_id" => $invitee->id]);
        CollectionInvitation::factory()->create([
            "invitee_id" => $invitee->id,
            "status" => InvitationStatus::Declined,
        ]);
        CollectionInvitation::factory()->create();

        $this->actingAs($invitee)
            ->getJson("/api/v1/invitations")
            ->assertOk()
            ->assertJsonCount(3, "data");

        $this->actingAs($invitee)
            ->getJson("/api/v1/invitations?filter[status]=pending")
            ->assertOk()
            ->assertJsonCount(2, "data");
    }

    public function test_invitee_can_accept_invitation_and_becomes_member(): void
    {
        $invitee = User::factory()->create();
        $collection = Collection::factory()->create();
        $invitation = CollectionInvitation::factory()->create([
            "collection_id" => $collection->id,
            "invitee_id" => $invitee->id,
        ]);

        $this->actingAs($invitee)
            ->postJson("/api/v1/invitations/{$invitation->id}/accept")
            ->assertOk()
            ->assertJsonPath("data.status", InvitationStatus::Accepted->value);

        $this->assertTrue($collection->hasMember($invitee));
        $this->assertSame(
            CollectionRole::Member->value,
            $collection->members()->whereKey($invitee->id)->first()?->getRelationValue("pivot")?->getAttribute("role"),
        );
    }

    public function test_invitee_can_decline_invitation(): void
    {
        $invitee = User::factory()->create();
        $collection = Collection::factory()->create();
        $invitation = CollectionInvitation::factory()->create([
            "collection_id" => $collection->id,
            "invitee_id" => $invitee->id,
        ]);

        $this->actingAs($invitee)
            ->postJson("/api/v1/invitations/{$invitation->id}/decline")
            ->assertOk()
            ->assertJsonPath("data.status", InvitationStatus::Declined->value);

        $this->assertFalse($collection->hasMember($invitee));
    }

    public function test_non_invitee_cannot_respond_to_invitation(): void
    {
        $user = User::factory()->create();
        $invitation = CollectionInvitation::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/invitations/{$invitation->id}/accept")
            ->assertForbidden();
    }

    public function test_cannot_respond_to_invitation_twice(): void
    {
        $invitee = User::factory()->create();
        $invitation = CollectionInvitation::factory()->create([
            "invitee_id" => $invitee->id,
            "status" => InvitationStatus::Declined,
        ]);

        $this->actingAs($invitee)
            ->postJson("/api/v1/invitations/{$invitation->id}/accept")
            ->assertStatus(422);
    }
}
