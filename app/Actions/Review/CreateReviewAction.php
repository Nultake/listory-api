<?php

namespace App\Actions\Review;

use App\Models\Review;
use App\Models\User;

class CreateReviewAction
{
    /**
     * Create a review for the given user.
     *
     * A soft-deleted review for the same media item is restored and
     * overwritten instead, since the database enforces one review per
     * user and media item across soft deletes.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Review
    {
        $trashed = Review::withTrashed()
            ->where("user_id", $user->id)
            ->where("media_item_id", $data["media_item_id"])
            ->first();

        if ($trashed !== null) {
            $trashed->restore();
            $trashed->fill([
                "rating" => $data["rating"],
                "comment" => $data["comment"] ?? null,
                "has_spoiler" => $data["has_spoiler"] ?? false,
            ]);
            $trashed->save();

            return $trashed;
        }

        return Review::create([
            "user_id" => $user->id,
            "media_item_id" => $data["media_item_id"],
            "rating" => $data["rating"],
            "comment" => $data["comment"] ?? null,
            "has_spoiler" => $data["has_spoiler"] ?? false,
        ]);
    }
}
