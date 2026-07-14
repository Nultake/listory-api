<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\MediaItem\CreateMediaItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\MediaItem\IndexMediaItemRequest;
use App\Http\Requests\MediaItem\StoreMediaItemRequest;
use App\Http\Requests\MediaItem\UpdateMediaItemRequest;
use App\Http\Resources\MediaItemResource;
use App\Models\MediaItem;
use App\Models\User;
use App\Services\MediaItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MediaItemController extends Controller
{
    public function __construct(private readonly MediaItemService $mediaItemService) {}

    /**
     * Display a paginated listing of media items.
     */
    public function index(IndexMediaItemRequest $request): AnonymousResourceCollection
    {
        /** @var array{type?: string} $filters */
        $filters = $request->validated("filter", []);

        $mediaItems = $this->mediaItemService->paginate(
            filters: $filters,
            sort: $request->validated("sort"),
            include: $request->validated("include"),
            perPage: (int) $request->validated("per_page", 15),
        );

        return MediaItemResource::collection($mediaItems);
    }

    /**
     * Store a newly created media item.
     */
    public function store(StoreMediaItemRequest $request, CreateMediaItemAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $mediaItem = $action->handle($user, $request->validated());

        return (new MediaItemResource($mediaItem))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified media item.
     */
    public function show(Request $request, MediaItem $mediaItem): MediaItemResource
    {
        $include = $request->query("include");

        return new MediaItemResource(
            $this->mediaItemService->loadIncludes($mediaItem, is_string($include) ? $include : null),
        );
    }

    /**
     * Update the specified media item.
     */
    public function update(UpdateMediaItemRequest $request, MediaItem $mediaItem): MediaItemResource
    {
        Gate::authorize("update", $mediaItem);

        return new MediaItemResource(
            $this->mediaItemService->update($mediaItem, $request->validated()),
        );
    }

    /**
     * Soft delete the specified media item.
     */
    public function destroy(MediaItem $mediaItem): Response
    {
        Gate::authorize("delete", $mediaItem);

        $mediaItem->delete();

        return response()->noContent();
    }
}
