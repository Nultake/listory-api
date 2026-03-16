<?php

namespace App\Models;

use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string|null $cover_image
 * @property bool $is_public
 * @property string $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $members
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MediaItem> $mediaItems
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CollectionInvitation> $invitations
 */
class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        "name",
        "description",
        "cover_image",
        "is_public",
        "user_id",
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "is_public" => "boolean",
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot("role")
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<MediaItem, $this>
     */
    public function mediaItems(): BelongsToMany
    {
        return $this->belongsToMany(MediaItem::class)->withTimestamps();
    }

    /**
     * @return HasMany<CollectionInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(CollectionInvitation::class);
    }
}
