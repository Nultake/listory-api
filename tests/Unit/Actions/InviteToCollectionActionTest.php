<?php

namespace Tests\Unit\Actions;

use App\Actions\Collection\InviteToCollectionAction;
use App\Enums\CollectionRole;
use App\Enums\InvitationStatus;
use App\Models\Collection;
use App\Models\CollectionInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InviteToCollectionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_pending_invitation(): void
    {
        $collection = Collection::factory()->create();
        $invitee = User::factory()->create();
        $action = new InviteToCollectionAction;

        $invitation = $action->handle($collection, $collection->owner, $invitee, "Join us!");

        $this->assertModelExists($invitation);
        $this->assertSame(InvitationStatus::Pending, $invitation->status);
        $this->assertSame($invitee->id, $invitation->invitee_id);
        $this->assertSame("Join us!", $invitation->message);
    }

    public function test_it_rejects_inviting_existing_member(): void
    {
        $collection = Collection::factory()->create();
        $member = User::factory()->create();
        $collection->members()->attach($member->id, ["role" => CollectionRole::Member->value]);
        $action = new InviteToCollectionAction;

        $this->expectException(ValidationException::class);

        $action->handle($collection, $collection->owner, $member);
    }

    public function test_it_rejects_duplicate_pending_invitation(): void
    {
        $collection = Collection::factory()->create();
        $invitee = User::factory()->create();
        CollectionInvitation::factory()->create([
            "collection_id" => $collection->id,
            "invitee_id" => $invitee->id,
            "status" => InvitationStatus::Pending,
        ]);
        $action = new InviteToCollectionAction;

        $this->expectException(ValidationException::class);

        $action->handle($collection, $collection->owner, $invitee);
    }

    public function test_it_reopens_declined_invitation(): void
    {
        $collection = Collection::factory()->create();
        $invitee = User::factory()->create();
        $declined = CollectionInvitation::factory()->create([
            "collection_id" => $collection->id,
            "invitee_id" => $invitee->id,
            "status" => InvitationStatus::Declined,
        ]);
        $action = new InviteToCollectionAction;

        $invitation = $action->handle($collection, $collection->owner, $invitee, "Second chance.");

        $this->assertSame($declined->id, $invitation->id);
        $this->assertSame(InvitationStatus::Pending, $invitation->status);
        $this->assertSame("Second chance.", $invitation->message);
        $this->assertSame($collection->owner->id, $invitation->inviter_id);
    }
}
