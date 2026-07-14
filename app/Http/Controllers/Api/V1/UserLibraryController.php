<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserLibrary\IndexUserLibraryRequest;
use App\Http\Resources\UserLibraryResource;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserLibraryController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    /**
     * Display the authenticated user's library: every media item they
     * have reviewed, together with their own rating and comment.
     */
    public function index(IndexUserLibraryRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{type?: string} $filters */
        $filters = $request->validated("filter", []);

        $entries = $this->reviewService->paginateForUser(
            user: $user,
            filters: $filters,
            sort: $request->validated("sort"),
            with: ["mediaItem.genres"],
            perPage: (int) $request->validated("per_page", 15),
        );

        return UserLibraryResource::collection($entries);
    }
}
