<?php

namespace App\Http\Resources;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Collection
 */
class CollectionResource extends JsonResource
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
            "name" => $this->name,
            "description" => $this->description,
            "cover_image" => $this->cover_image,
            "is_public" => $this->is_public,
            "owner_id" => $this->user_id,
            "owner" => new UserResource($this->whenLoaded("owner")),
            "members" => CollectionMemberResource::collection($this->whenLoaded("members")),
            "media_items" => MediaItemResource::collection($this->whenLoaded("mediaItems")),
            "members_count" => $this->whenCounted("members"),
            "media_items_count" => $this->whenCounted("mediaItems"),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
