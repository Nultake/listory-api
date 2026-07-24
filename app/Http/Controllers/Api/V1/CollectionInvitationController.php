<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Collection\InviteToCollectionAction;
use App\Actions\Collection\RespondToInvitationAction;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Collection\IndexInvitationRequest;
use App\Http\Requests\Collection\InviteMemberRequest;
use App\Http\Resources\CollectionInvitationResource;
use App\Models\Collection;
use App\Models\CollectionInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CollectionInvitationController extends Controller
{
    /**
     * Display a paginated listing of the authenticated user's received invitations.
     */
    public function index(IndexInvitationRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{status?: string} $filters */
        $filters = $request->validated("filter", []);

        $invitations = $user->receivedInvitations()
            ->when(isset($filters["status"]), function ($query) use ($filters): void {
                $query->where("status", $filters["status"]);
            })
            ->with(["collection", "inviter"])
            ->latest()
            ->paginate((int) $request->validated("per_page", 15));

        return CollectionInvitationResource::collection($invitations);
    }

    /**
     * Invite a user to the collection by email.
     */
    public function store(InviteMemberRequest $request, Collection $collection, InviteToCollectionAction $action): JsonResponse
    {
        Gate::authorize("invite", $collection);

        /** @var User $inviter */
        $inviter = $request->user();

        /** @var User $invitee */
        $invitee = User::query()->where("email", $request->validated("email"))->firstOrFail();

        $invitation = $action->handle($collection, $inviter, $invitee, $request->validated("message"));

        return (new CollectionInvitationResource($invitation->load(["collection", "invitee"])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Accept the invitation and join the collection.
     */
    public function accept(CollectionInvitation $invitation, RespondToInvitationAction $action): CollectionInvitationResource
    {
        Gate::authorize("respond", $invitation);

        return new CollectionInvitationResource(
            $action->handle($invitation, InvitationStatus::Accepted)->load("collection"),
        );
    }

    /**
     * Decline the invitation.
     */
    public function decline(CollectionInvitation $invitation, RespondToInvitationAction $action): CollectionInvitationResource
    {
        Gate::authorize("respond", $invitation);

        return new CollectionInvitationResource(
            $action->handle($invitation, InvitationStatus::Declined)->load("collection"),
        );
    }
}
