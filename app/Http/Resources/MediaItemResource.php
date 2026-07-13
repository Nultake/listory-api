<?php

namespace App\Http\Resources;

use App\Models\MediaItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MediaItem
 */
class MediaItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "type" => $this->type,
            "description" => $this->description,
            "cover_image" => $this->cover_image,
            "release_date" => $this->release_date?->toDateString(),
            "external_id" => $this->external_id,
            "external_source" => $this->external_source,
            "created_by" => $this->created_by,
            "genres" => GenreResource::collection($this->whenLoaded("genres")),
            "reviews" => ReviewResource::collection($this->whenLoaded("reviews")),
            "creator" => new UserResource($this->whenLoaded("creator")),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
