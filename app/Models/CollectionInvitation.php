<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Database\Factories\CollectionInvitationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $collection_id
 * @property string $inviter_id
 * @property string $invitee_id
 * @property InvitationStatus $status
 * @property string|null $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection $collection
 * @property-read User $inviter
 * @property-read User $invitee
 */
class CollectionInvitation extends Model
{
    /** @use HasFactory<CollectionInvitationFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        "collection_id",
        "inviter_id",
        "invitee_id",
        "status",
        "message",
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "status" => InvitationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, "inviter_id");
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, "invitee_id");
    }
}
