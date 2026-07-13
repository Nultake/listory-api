<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A library entry: a media item together with the user's own review of it.
 *
 * @mixin Review
 */
class UserLibraryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "review_id" => $this->id,
            "rating" => $this->rating,
            "comment" => $this->comment,
            "has_spoiler" => $this->has_spoiler,
            "media_item" => new MediaItemResource($this->whenLoaded("mediaItem")),
            "reviewed_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
