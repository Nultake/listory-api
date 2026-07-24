<?php

namespace App\Actions\Collection;

use App\Models\Collection;
use App\Models\MediaItem;

class AddItemToCollectionAction
{
    /**
     * Attach a media item to the collection.
     */
    public function handle(Collection $collection, MediaItem $mediaItem): MediaItem
    {
        $collection->mediaItems()->attach($mediaItem->id);

        return $mediaItem;
    }
}
