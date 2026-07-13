<?php

namespace App\Services;

use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReviewService
{
    /**
     * Relations that may be eager loaded via the ?include= parameter.
     *
     * @var list<string>
     */
    private const array ALLOWED_INCLUDES = ["mediaItem", "mediaItem.genres", "user"];

    /**
     * Columns that may be sorted via the ?sort= parameter.
     *
     * @var list<string>
     */
    private const array ALLOWED_SORTS = ["created_at", "updated_at", "rating"];

    /**
     * Paginate the given user's reviews with optional filtering, sorting, and eager loading.
     *
     * @param  array{media_item_id?: string, type?: string}  $filters
     * @param  list<string>  $with  Relations to always eager load, merged with ?include=
     * @return LengthAwarePaginator<int, Review>
     */
    public function paginateForUser(
        User $user,
        array $filters = [],
        ?string $sort = null,
        ?string $include = null,
        array $with = [],
        int $perPage = 15,
    ): LengthAwarePaginator {
        [$column, $direction] = $this->parseSort($sort);

        $relations = array_values(array_unique([...$with, ...$this->parseIncludes($include)]));

        return $user->reviews()
            ->when(isset($filters["media_item_id"]), function ($query) use ($filters): void {
                $query->where("media_item_id", $filters["media_item_id"]);
            })
            ->when(isset($filters["type"]), function ($query) use ($filters): void {
                $query->whereHas("mediaItem", function ($mediaItemQuery) use ($filters): void {
                    $mediaItemQuery->where("type", $filters["type"]);
                });
            })
            ->with($relations)
            ->orderBy($column, $direction)
            ->paginate($perPage);
    }

    /**
     * Intersect the requested includes with the allowlist.
     *
     * @return list<string>
     */
    private function parseIncludes(?string $include): array
    {
        if ($include === null || trim($include) === "") {
            return [];
        }

        $requested = array_map(trim(...), explode(",", $include));

        return array_values(array_intersect($requested, self::ALLOWED_INCLUDES));
    }

    /**
     * Parse a ?sort= value ("-created_at" means descending) into column and direction.
     *
     * @return array{0: string, 1: string}
     */
    private function parseSort(?string $sort): array
    {
        if ($sort === null || $sort === "") {
            return ["created_at", "desc"];
        }

        $direction = str_starts_with($sort, "-") ? "desc" : "asc";
        $column = ltrim($sort, "-");

        if (! in_array($column, self::ALLOWED_SORTS, true)) {
            return ["created_at", "desc"];
        }

        return [$column, $direction];
    }
}
