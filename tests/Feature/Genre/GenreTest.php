<?php

namespace Tests\Feature\Genre;

use App\Models\User;
use Database\Seeders\GenreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_genres(): void
    {
        $this->seed(GenreSeeder::class);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/v1/genres");

        $response->assertOk()
            ->assertJsonStructure([
                "data" => [["id", "name", "slug"]],
                "links",
                "meta",
            ])
            ->assertJsonPath("data.0.name", "Action");

        $this->assertSame(21, $response->json("meta.total"));
    }

    public function test_genre_seeder_is_idempotent(): void
    {
        $this->seed(GenreSeeder::class);
        $this->seed(GenreSeeder::class);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/v1/genres");

        $this->assertSame(21, $response->json("meta.total"));
    }

    public function test_unauthenticated_user_cannot_list_genres(): void
    {
        $this->getJson("/api/v1/genres")->assertStatus(401);
    }
}
