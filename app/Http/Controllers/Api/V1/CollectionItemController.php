<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Collection\AddItemToCollectionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Collection\AddItemRequest;
use App\Http\Resources\MediaItemResource;
use App\Models\Collection;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CollectionItemController extends Controller
{
    /**
     * Add a media item to the collection.
     */
    public function store(AddItemRequest $request, Collection $collection, AddItemToCollectionAction $action): JsonResponse
    {
        Gate::authorize("addItem", $collection);

        /** @var MediaItem $mediaItem */
        $mediaItem = MediaItem::query()->findOrFail($request->validated("media_item_id"));

        $action->handle($collection, $mediaItem);

        return (new MediaItemResource($mediaItem->load("genres")))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove a media item from the collection.
     */
    public function destroy(Collection $collection, MediaItem $mediaItem): Response
    {
        Gate::authorize("removeItem", $collection);

        $detached = $collection->mediaItems()->detach($mediaItem->id);

        abort_if($detached === 0, 404, "The media item is not in this collection.");

        return response()->noContent();
    }
}
