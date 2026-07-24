<?php

namespace App\Http\Resources;

use App\Models\CollectionInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CollectionInvitation
 */
class CollectionInvitationResource extends JsonResource
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
            "collection_id" => $this->collection_id,
            "inviter_id" => $this->inviter_id,
            "invitee_id" => $this->invitee_id,
            "status" => $this->status,
            "message" => $this->message,
            "collection" => new CollectionResource($this->whenLoaded("collection")),
            "inviter" => new UserResource($this->whenLoaded("inviter")),
            "invitee" => new UserResource($this->whenLoaded("invitee")),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
