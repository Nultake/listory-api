<?php

namespace App\Policies;

use App\Models\MediaItem;
use App\Models\User;

class MediaItemPolicy
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
    public function view(User $user, MediaItem $mediaItem): bool
    {
        return true;
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
    public function update(User $user, MediaItem $mediaItem): bool
    {
        return $user->id === $mediaItem->created_by;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MediaItem $mediaItem): bool
    {
        return $user->id === $mediaItem->created_by;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MediaItem $mediaItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MediaItem $mediaItem): bool
    {
        return false;
    }
}
