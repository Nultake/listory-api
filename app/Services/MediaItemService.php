<?php

namespace App\Services;

use App\Models\MediaItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class MediaItemService
{
    /**
     * Relations that may be eager loaded via the ?include= parameter.
     *
     * @var list<string>
     */
    private const array ALLOWED_INCLUDES = ["genres", "reviews", "reviews.user", "creator"];

    /**
     * Columns that may be sorted via the ?sort= parameter.
     *
     * @var list<string>
     */
    private const array ALLOWED_SORTS = ["created_at", "updated_at", "title", "release_date"];

    /**
     * Paginate media items with optional filtering, sorting, and eager loading.
     *
     * @param  array{type?: string}  $filters
     * @return LengthAwarePaginator<int, MediaItem>
     */
    public function paginate(array $filters = [], ?string $sort = null, ?string $include = null, int $perPage = 15): LengthAwarePaginator
    {
        [$column, $direction] = $this->parseSort($sort);

        return MediaItem::query()
            ->when(isset($filters["type"]), function ($query) use ($filters): void {
                $query->where("type", $filters["type"]);
            })
            ->with($this->parseIncludes($include))
            ->orderBy($column, $direction)
            ->paginate($perPage);
    }

    /**
     * Eager load the relations requested via the ?include= parameter.
     */
    public function loadIncludes(MediaItem $mediaItem, ?string $include): MediaItem
    {
        $relations = $this->parseIncludes($include);

        if ($relations !== []) {
            $mediaItem->load($relations);
        }

        return $mediaItem;
    }

    /**
     * Update a media item and sync its genres when provided.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MediaItem $mediaItem, array $data): MediaItem
    {
        $mediaItem->fill(Arr::except($data, ["genre_ids"]));
        $mediaItem->save();

        if (array_key_exists("genre_ids", $data)) {
            $mediaItem->genres()->sync($data["genre_ids"] ?? []);
        }

        return $mediaItem->load("genres");
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
