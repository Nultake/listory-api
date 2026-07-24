<?php

namespace Tests\Unit\Actions;

use App\Actions\Collection\RespondToInvitationAction;
use App\Enums\InvitationStatus;
use App\Models\CollectionInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RespondToInvitationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_adds_invitee_as_member(): void
    {
        $invitation = CollectionInvitation::factory()->create();
        $action = new RespondToInvitationAction;

        $action->handle($invitation, InvitationStatus::Accepted);

        $this->assertSame(InvitationStatus::Accepted, $invitation->fresh()?->status);
        $this->assertTrue($invitation->collection->hasMember($invitation->invitee));
    }

    public function test_declining_does_not_add_member(): void
    {
        $invitation = CollectionInvitation::factory()->create();
        $action = new RespondToInvitationAction;

        $action->handle($invitation, InvitationStatus::Declined);

        $this->assertSame(InvitationStatus::Declined, $invitation->fresh()?->status);
        $this->assertFalse($invitation->collection->hasMember($invitation->invitee));
    }

    public function test_it_rejects_responding_twice(): void
    {
        $invitation = CollectionInvitation::factory()->create([
            "status" => InvitationStatus::Accepted,
        ]);
        $action = new RespondToInvitationAction;

        $this->expectException(ValidationException::class);

        $action->handle($invitation, InvitationStatus::Declined);
    }
}
