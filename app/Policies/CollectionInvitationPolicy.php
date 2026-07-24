<?php

namespace App\Policies;

use App\Models\CollectionInvitation;
use App\Models\User;

class CollectionInvitationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CollectionInvitation $invitation): bool
    {
        return $user->id === $invitation->invitee_id || $user->id === $invitation->inviter_id;
    }

    /**
     * Determine whether the user can respond (accept or decline) to the invitation.
     */
    public function respond(User $user, CollectionInvitation $invitation): bool
    {
        return $user->id === $invitation->invitee_id;
    }
}
