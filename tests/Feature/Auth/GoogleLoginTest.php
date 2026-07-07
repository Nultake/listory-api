<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\GoogleTokenVerifier;
use App\Services\GoogleUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogleUser(
        string $id = "google-123",
        string $name = "John Doe",
        string $email = "john@example.com",
    ): void {
        $this->mock(GoogleTokenVerifier::class, function (MockInterface $mock) use ($id, $name, $email): void {
            $mock->shouldReceive("verify")
                ->with("valid-google-token")
                ->andReturn(new GoogleUser($id, $email, $name));
        });
    }

    private function mockGoogleFailure(): void
    {
        $this->mock(GoogleTokenVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive("verify")->andReturnNull();
        });
    }

    public function test_new_user_can_sign_in_with_google(): void
    {
        $this->mockGoogleUser();

        $response = $this->postJson("/api/v1/auth/google", [
            "token" => "valid-google-token",
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                "user" => ["id", "name", "email", "email_verified", "created_at", "updated_at"],
                "token",
            ])
            ->assertJsonPath("user.name", "John Doe")
            ->assertJsonPath("user.email", "john@example.com")
            ->assertJsonPath("user.email_verified", true);

        $user = User::query()->where("email", "john@example.com")->first();
        $this->assertModelExists($user);
        $this->assertSame("google-123", $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_existing_google_user_can_sign_in(): void
    {
        $existingUser = User::factory()->withGoogle()->create([
            "google_id" => "google-123",
            "email" => "john@example.com",
        ]);

        $this->mockGoogleUser();

        $response = $this->postJson("/api/v1/auth/google", [
            "token" => "valid-google-token",
        ]);

        $response->assertOk()
            ->assertJsonPath("user.id", $existingUser->id);

        $this->assertDatabaseCount("users", 1);
    }

    public function test_existing_email_user_gets_google_linked(): void
    {
        $existingUser = User::factory()->create([
            "email" => "john@example.com",
        ]);

        $this->assertNull($existingUser->google_id);

        $this->mockGoogleUser();

        $response = $this->postJson("/api/v1/auth/google", [
            "token" => "valid-google-token",
        ]);

        $response->assertOk()
            ->assertJsonPath("user.id", $existingUser->id);

        $existingUser->refresh();
        $this->assertSame("google-123", $existingUser->google_id);
        $this->assertDatabaseCount("users", 1);
    }

    public function test_unverified_email_user_gets_verified_on_google_link(): void
    {
        $existingUser = User::factory()->unverified()->create([
            "email" => "john@example.com",
        ]);

        $this->assertNull($existingUser->email_verified_at);

        $this->mockGoogleUser();

        $response = $this->postJson("/api/v1/auth/google", [
            "token" => "valid-google-token",
        ]);

        $response->assertOk()
            ->assertJsonPath("user.email_verified", true);

        $existingUser->refresh();
        $this->assertNotNull($existingUser->email_verified_at);
    }

    public function test_google_login_fails_with_invalid_token(): void
    {
        $this->mockGoogleFailure();

        $response = $this->postJson("/api/v1/auth/google", [
            "token" => "invalid-token",
        ]);

        $response->assertStatus(401)
            ->assertJsonPath("message", "Invalid Google token.");
    }

    public function test_google_login_requires_token(): void
    {
        $response = $this->postJson("/api/v1/auth/google", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(["token"]);
    }

    public function test_google_login_returns_correct_response_structure(): void
    {
        $this->mockGoogleUser();

        $response = $this->postJson("/api/v1/auth/google", [
            "token" => "valid-google-token",
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                "user" => ["id", "name", "email", "email_verified", "created_at", "updated_at"],
                "token",
            ]);
    }
}
