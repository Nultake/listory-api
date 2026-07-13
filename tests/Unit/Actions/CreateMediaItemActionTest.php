<?php

namespace Tests\Unit\Actions;

use App\Actions\MediaItem\CreateMediaItemAction;
use App\Enums\MediaType;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateMediaItemActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_media_item_for_the_user(): void
    {
        $user = User::factory()->create();

        $mediaItem = new CreateMediaItemAction()->handle($user, [
            "title" => "Breaking Bad",
            "type" => "series",
            "description" => "Chemistry teacher goes rogue.",
        ]);

        $this->assertModelExists($mediaItem);
        $this->assertSame($user->id, $mediaItem->created_by);
        $this->assertSame(MediaType::Series, $mediaItem->type);
    }

    public function test_it_attaches_genres(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(3)->create();

        $mediaItem = new CreateMediaItemAction()->handle($user, [
            "title" => "Elden Ring",
            "type" => "game",
            "genre_ids" => $genres->pluck("id")->all(),
        ]);

        $this->assertCount(3, $mediaItem->genres);
    }

    public function test_it_creates_without_genres(): void
    {
        $user = User::factory()->create();

        $mediaItem = new CreateMediaItemAction()->handle($user, [
            "title" => "Dune",
            "type" => "film",
        ]);

        $this->assertCount(0, $mediaItem->genres);
    }
}
