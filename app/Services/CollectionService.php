<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CollectionService
{
    /**
     * Relations that may be eager loaded via the ?include= parameter.
     *
     * @var list<string>
     */
    private const array ALLOWED_INCLUDES = ["owner", "members", "mediaItems"];

    /**
     * Columns that may be sorted via the ?sort= parameter.
     *
     * @var list<string>
     */
    private const array ALLOWED_SORTS = ["created_at", "updated_at", "name"];

    /**
     * Paginate the collections the given user belongs to.
     *
     * @return LengthAwarePaginator<int, Collection&object{pivot: Pivot}>
     */
    public function paginateForUser(User $user, ?string $sort = null, ?string $include = null, int $perPage = 15): LengthAwarePaginator
    {
        [$column, $direction] = $this->parseSort($sort);

        return $user->collections()
            ->with($this->parseIncludes($include))
            ->withCount(["members", "mediaItems"])
            ->orderBy("collections.".$column, $direction)
            ->paginate($perPage);
    }

    /**
     * Load everything the collection detail view needs: the owner, all
     * members, and every media item with the members' reviews side by side.
     */
    public function loadDetail(Collection $collection): Collection
    {
        $memberIds = $collection->members()->pluck("users.id");

        $collection->load([
            "owner",
            "members",
            "mediaItems.genres",
            "mediaItems.reviews" => function ($query) use ($memberIds): void {
                $query->whereIn("user_id", $memberIds)->with("user");
            },
        ]);

        $collection->loadCount(["members", "mediaItems"]);

        return $collection;
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
