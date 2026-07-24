<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Collection\CreateCollectionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Collection\IndexCollectionRequest;
use App\Http\Requests\Collection\StoreCollectionRequest;
use App\Http\Requests\Collection\UpdateCollectionRequest;
use App\Http\Resources\CollectionDetailResource;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use App\Models\User;
use App\Services\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CollectionController extends Controller
{
    public function __construct(private readonly CollectionService $collectionService) {}

    /**
     * Display a paginated listing of the collections the user belongs to.
     */
    public function index(IndexCollectionRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $collections = $this->collectionService->paginateForUser(
            user: $user,
            sort: $request->validated("sort"),
            include: $request->validated("include"),
            perPage: (int) $request->validated("per_page", 15),
        );

        return CollectionResource::collection($collections);
    }

    /**
     * Store a newly created collection owned by the authenticated user.
     */
    public function store(StoreCollectionRequest $request, CreateCollectionAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $collection = $action->handle($user, $request->validated());

        return (new CollectionResource($collection->loadCount(["members", "mediaItems"])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the collection detail view: items with all members' reviews.
     */
    public function show(Collection $collection): CollectionDetailResource
    {
        Gate::authorize("view", $collection);

        return new CollectionDetailResource($this->collectionService->loadDetail($collection));
    }

    /**
     * Update the specified collection.
     */
    public function update(UpdateCollectionRequest $request, Collection $collection): CollectionResource
    {
        Gate::authorize("update", $collection);

        $collection->fill($request->validated());
        $collection->save();

        return new CollectionResource($collection->loadCount(["members", "mediaItems"]));
    }

    /**
     * Delete the specified collection.
     */
    public function destroy(Collection $collection): Response
    {
        Gate::authorize("delete", $collection);

        $collection->delete();

        return response()->noContent();
    }
}
