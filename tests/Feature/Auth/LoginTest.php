<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            "email" => "john@example.com",
            "password" => "password123",
        ]);

        $response = $this->postJson("/api/v1/auth/login", [
            "email" => "john@example.com",
            "password" => "password123",
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                "user" => ["id", "name", "email", "email_verified", "created_at", "updated_at"],
                "token",
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            "email" => "john@example.com",
            "password" => "password123",
        ]);

        $response = $this->postJson("/api/v1/auth/login", [
            "email" => "john@example.com",
            "password" => "wrong-password",
        ]);

        $response->assertStatus(401)
            ->assertJsonPath("message", "Invalid credentials.");
    }

    public function test_login_requires_email(): void
    {
        $response = $this->postJson("/api/v1/auth/login", [
            "password" => "password123",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(["email"]);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->postJson("/api/v1/auth/login", [
            "email" => "john@example.com",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(["password"]);
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create([
            "email" => "john@example.com",
            "password" => "password123",
        ]);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson("/api/v1/auth/login", [
                "email" => "john@example.com",
                "password" => "wrong-password",
            ])->assertStatus(401);
        }

        $response = $this->postJson("/api/v1/auth/login", [
            "email" => "john@example.com",
            "password" => "password123",
        ]);

        $response->assertStatus(429);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken("auth-token")->plainTextToken;

        $response = $this->withHeader("Authorization", "Bearer {$token}")
            ->postJson("/api/v1/auth/logout");

        $response->assertOk()
            ->assertJsonPath("message", "Logged out successfully.");

        $this->assertDatabaseCount("personal_access_tokens", 0);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson("/api/v1/auth/logout");

        $response->assertStatus(401);
    }

    public function test_user_can_get_own_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/auth/me");

        $response->assertOk()
            ->assertJsonPath("user.id", $user->id)
            ->assertJsonPath("user.email", $user->email);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson("/api/v1/auth/me");

        $response->assertStatus(401);
    }
}
