<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $user_id
 * @property string $media_item_id
 * @property int $rating
 * @property string|null $comment
 * @property bool $has_spoiler
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User $user
 * @property-read MediaItem $mediaItem
 */
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        "user_id",
        "media_item_id",
        "rating",
        "comment",
        "has_spoiler",
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "rating" => "integer",
            "has_spoiler" => "boolean",
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<MediaItem, $this>
     */
    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
    }
}
