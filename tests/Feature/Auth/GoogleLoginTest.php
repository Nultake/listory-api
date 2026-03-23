<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function mockSocialiteUser(
        string $id = "google-123",
        string $name = "John Doe",
        string $email = "john@example.com",
    ): void {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive("getId")->andReturn($id);
        $socialiteUser->shouldReceive("getName")->andReturn($name);
        $socialiteUser->shouldReceive("getEmail")->andReturn($email);

        $driver = Mockery::mock(GoogleProvider::class);
        $driver->shouldReceive("userFromToken")
            ->with("valid-google-token")
            ->andReturn($socialiteUser);

        Socialite::shouldReceive("driver")
            ->with("google")
            ->andReturn($driver);
    }

    private function mockSocialiteFailure(): void
    {
        $driver = Mockery::mock(GoogleProvider::class);
        $driver->shouldReceive("userFromToken")
            ->andThrow(new \Exception("Invalid token"));

        Socialite::shouldReceive("driver")
            ->with("google")
            ->andReturn($driver);
    }

    public function test_new_user_can_sign_in_with_google(): void
    {
        $this->mockSocialiteUser();

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

        $this->mockSocialiteUser();

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

        $this->mockSocialiteUser();

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

        $this->mockSocialiteUser();

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
        $this->mockSocialiteFailure();

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
        $this->mockSocialiteUser();

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
