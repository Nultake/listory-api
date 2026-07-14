<?php

namespace App\Actions\Review;

use App\Models\Review;

class UpdateReviewAction
{
    /**
     * Update the given review.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Review $review, array $data): Review
    {
        $review->fill($data);
        $review->save();

        return $review;
    }
}
