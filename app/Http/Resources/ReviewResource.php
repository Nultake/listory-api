<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
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
            "user_id" => $this->user_id,
            "media_item_id" => $this->media_item_id,
            "rating" => $this->rating,
            "comment" => $this->comment,
            "has_spoiler" => $this->has_spoiler,
            "user" => new UserResource($this->whenLoaded("user")),
            "media_item" => new MediaItemResource($this->whenLoaded("mediaItem")),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
