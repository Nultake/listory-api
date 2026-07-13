<?php

namespace App\Actions\MediaItem;

use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Support\Arr;

class CreateMediaItemAction
{
    /**
     * Create a media item owned by the given user and attach its genres.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): MediaItem
    {
        $mediaItem = MediaItem::create([
            ...Arr::except($data, ["genre_ids"]),
            "created_by" => $user->id,
        ]);

        if (! empty($data["genre_ids"])) {
            $mediaItem->genres()->attach($data["genre_ids"]);
        }

        return $mediaItem->load("genres");
    }
}
