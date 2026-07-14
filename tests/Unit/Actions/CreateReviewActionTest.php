<?php

namespace Tests\Unit\Actions;

use App\Actions\Review\CreateReviewAction;
use App\Models\MediaItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateReviewActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_review(): void
    {
        $user = User::factory()->create();
        $mediaItem = MediaItem::factory()->create();

        $review = new CreateReviewAction()->handle($user, [
            "media_item_id" => $mediaItem->id,
            "rating" => 8,
            "comment" => "Great.",
            "has_spoiler" => true,
        ]);

        $this->assertModelExists($review);
        $this->assertSame($user->id, $review->user_id);
        $this->assertSame(8, $review->rating);
        $this->assertTrue($review->has_spoiler);
    }

    public function test_it_defaults_optional_fields(): void
    {
        $user = User::factory()->create();
        $mediaItem = MediaItem::factory()->create();

        $review = new CreateReviewAction()->handle($user, [
            "media_item_id" => $mediaItem->id,
            "rating" => 6,
        ]);

        $this->assertNull($review->comment);
        $this->assertFalse($review->has_spoiler);
    }

    public function test_it_restores_a_soft_deleted_review_instead_of_creating(): void
    {
        $user = User::factory()->create();
        $existing = Review::factory()->create([
            "user_id" => $user->id,
            "rating" => 2,
            "comment" => "Old take.",
        ]);
        $existing->delete();

        $review = new CreateReviewAction()->handle($user, [
            "media_item_id" => $existing->media_item_id,
            "rating" => 9,
            "comment" => "New take.",
        ]);

        $this->assertSame($existing->id, $review->id);
        $this->assertFalse($review->trashed());
        $this->assertSame(9, $review->rating);
        $this->assertSame("New take.", $review->comment);
        $this->assertSame(1, Review::withTrashed()->count());
    }
}
