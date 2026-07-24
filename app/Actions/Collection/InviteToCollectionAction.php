<?php

namespace App\Actions\Collection;

use App\Enums\InvitationStatus;
use App\Models\Collection;
use App\Models\CollectionInvitation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class InviteToCollectionAction
{
    /**
     * Invite a user to join the collection.
     *
     * A previously declined invitation is re-opened instead of creating a
     * new row, since the database enforces one invitation per collection
     * and invitee.
     */
    public function handle(Collection $collection, User $inviter, User $invitee, ?string $message = null): CollectionInvitation
    {
        if ($collection->hasMember($invitee)) {
            throw ValidationException::withMessages([
                "email" => "This user is already a member of the collection.",
            ]);
        }

        $existing = CollectionInvitation::query()
            ->where("collection_id", $collection->id)
            ->where("invitee_id", $invitee->id)
            ->first();

        if ($existing !== null && $existing->status === InvitationStatus::Pending) {
            throw ValidationException::withMessages([
                "email" => "This user already has a pending invitation to the collection.",
            ]);
        }

        if ($existing !== null) {
            $existing->fill([
                "inviter_id" => $inviter->id,
                "status" => InvitationStatus::Pending,
                "message" => $message,
            ]);
            $existing->save();

            return $existing;
        }

        return CollectionInvitation::create([
            "collection_id" => $collection->id,
            "inviter_id" => $inviter->id,
            "invitee_id" => $invitee->id,
            "status" => InvitationStatus::Pending,
            "message" => $message,
        ]);
    }
}
