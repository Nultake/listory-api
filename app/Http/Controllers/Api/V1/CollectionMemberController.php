<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionMemberResource;
use App\Models\Collection;
use App\Models\CollectionInvitation;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CollectionMemberController extends Controller
{
    /**
     * Display a paginated listing of the collection's members.
     */
    public function index(Collection $collection): AnonymousResourceCollection
    {
        Gate::authorize("view", $collection);

        return CollectionMemberResource::collection($collection->members()->paginate(15));
    }

    /**
     * Remove a member from the collection.
     *
     * The owner can remove any member; a member can remove themselves
     * (leave the collection). The invitation record is deleted as well so
     * the user can be invited again later.
     */
    public function destroy(Collection $collection, User $member): Response
    {
        Gate::authorize("removeMember", [$collection, $member]);

        abort_unless($collection->hasMember($member), 404, "This user is not a member of the collection.");

        $collection->members()->detach($member->id);

        CollectionInvitation::query()
            ->where("collection_id", $collection->id)
            ->where("invitee_id", $member->id)
            ->delete();

        return response()->noContent();
    }
}
