<?php

namespace Tests\Unit\Actions;

use App\Actions\Collection\CreateCollectionAction;
use App\Enums\CollectionRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCollectionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_collection_and_registers_owner_as_member(): void
    {
        $user = User::factory()->create();
        $action = new CreateCollectionAction;

        $collection = $action->handle($user, [
            "name" => "Anime We Watched",
            "description" => "Shared watchlist.",
            "is_public" => true,
        ]);

        $this->assertModelExists($collection);
        $this->assertSame("Anime We Watched", $collection->name);
        $this->assertTrue($collection->is_public);
        $this->assertSame($user->id, $collection->user_id);
        $this->assertTrue($collection->hasMember($user));
        $this->assertSame(
            CollectionRole::Owner->value,
            $collection->members()->whereKey($user->id)->first()?->getRelationValue("pivot")?->getAttribute("role"),
        );
    }

    public function test_it_defaults_to_private_collection(): void
    {
        $user = User::factory()->create();
        $action = new CreateCollectionAction;

        $collection = $action->handle($user, ["name" => "Secret Stash"]);

        $this->assertFalse($collection->is_public);
        $this->assertNull($collection->description);
    }
}
