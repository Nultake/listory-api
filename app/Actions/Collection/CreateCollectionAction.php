<?php

namespace App\Actions\Collection;

use App\Enums\CollectionRole;
use App\Models\Collection;
use App\Models\User;

class CreateCollectionAction
{
    /**
     * Create a collection owned by the given user and register the
     * owner as its first member.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Collection
    {
        $collection = Collection::create([
            "name" => $data["name"],
            "description" => $data["description"] ?? null,
            "cover_image" => $data["cover_image"] ?? null,
            "is_public" => $data["is_public"] ?? false,
            "user_id" => $user->id,
        ]);

        $collection->members()->attach($user->id, ["role" => CollectionRole::Owner->value]);

        return $collection;
    }
}
