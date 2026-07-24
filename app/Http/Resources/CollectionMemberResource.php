<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A collection member: the user plus their role from the pivot table.
 *
 * @mixin User
 */
class CollectionMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pivot = $this->resource->getRelationValue("pivot");

        return [
            "id" => $this->id,
            "name" => $this->name,
            "email" => $this->email,
            "role" => $pivot instanceof Pivot ? $pivot->getAttribute("role") : null,
            "joined_at" => $pivot instanceof Pivot ? $pivot->getAttribute("created_at") : null,
        ];
    }
}
