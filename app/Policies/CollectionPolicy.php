<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;

class CollectionPolicy
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
    public function view(User $user, Collection $collection): bool
    {
        return $collection->is_public || $collection->hasMember($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id;
    }

    /**
     * Determine whether the user can add items to the collection.
     */
    public function addItem(User $user, Collection $collection): bool
    {
        return $collection->hasMember($user);
    }

    /**
     * Determine whether the user can remove items from the collection.
     */
    public function removeItem(User $user, Collection $collection): bool
    {
        return $collection->hasMember($user);
    }

    /**
     * Determine whether the user can invite others to the collection.
     */
    public function invite(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id;
    }

    /**
     * Determine whether the user can remove the given member from the collection.
     *
     * The owner can never be removed. The owner may remove any other
     * member, and a member may remove themselves (leave the collection).
     */
    public function removeMember(User $user, Collection $collection, User $member): bool
    {
        if ($member->id === $collection->user_id) {
            return false;
        }

        return $user->id === $collection->user_id || $user->id === $member->id;
    }
}
