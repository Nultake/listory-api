<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $user_id
 * @property string $code
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read User $user
 */
class EmailVerificationCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        "user_id",
        "code",
        "expires_at",
    ];

    protected function casts(): array
    {
        return [
            "expires_at" => "datetime",
            "created_at" => "datetime",
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
