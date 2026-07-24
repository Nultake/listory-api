<?php

namespace App\Actions\Collection;

use App\Enums\CollectionRole;
use App\Enums\InvitationStatus;
use App\Models\CollectionInvitation;
use Illuminate\Validation\ValidationException;

class RespondToInvitationAction
{
    /**
     * Accept or decline a pending invitation. Accepting adds the invitee
     * to the collection as a member.
     */
    public function handle(CollectionInvitation $invitation, InvitationStatus $status): CollectionInvitation
    {
        if ($invitation->status !== InvitationStatus::Pending) {
            throw ValidationException::withMessages([
                "invitation" => "This invitation has already been responded to.",
            ]);
        }

        $invitation->status = $status;
        $invitation->save();

        if ($status === InvitationStatus::Accepted) {
            $invitation->collection->members()->syncWithoutDetaching([
                $invitation->invitee_id => ["role" => CollectionRole::Member->value],
            ]);
        }

        return $invitation;
    }
}
