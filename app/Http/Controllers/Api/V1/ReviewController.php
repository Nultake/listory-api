<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Review\CreateReviewAction;
use App\Actions\Review\UpdateReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\IndexReviewRequest;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Requests\Review\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    /**
     * Display a paginated listing of the authenticated user's reviews.
     */
    public function index(IndexReviewRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{media_item_id?: string, type?: string} $filters */
        $filters = $request->validated("filter", []);

        $reviews = $this->reviewService->paginateForUser(
            user: $user,
            filters: $filters,
            sort: $request->validated("sort"),
            include: $request->validated("include"),
            perPage: (int) $request->validated("per_page", 15),
        );

        return ReviewResource::collection($reviews);
    }

    /**
     * Store a newly created review.
     */
    public function store(StoreReviewRequest $request, CreateReviewAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $review = $action->handle($user, $request->validated());

        return (new ReviewResource($review->load("mediaItem")))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified review.
     */
    public function show(Review $review): ReviewResource
    {
        Gate::authorize("view", $review);

        return new ReviewResource($review->load(["mediaItem", "user"]));
    }

    /**
     * Update the specified review.
     */
    public function update(UpdateReviewRequest $request, Review $review, UpdateReviewAction $action): ReviewResource
    {
        Gate::authorize("update", $review);

        return new ReviewResource($action->handle($review, $request->validated()));
    }

    /**
     * Soft delete the specified review.
     */
    public function destroy(Review $review): Response
    {
        Gate::authorize("delete", $review);

        $review->delete();

        return response()->noContent();
    }
}
