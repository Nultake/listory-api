<?php

namespace Tests\Feature\Auth;

use App\Models\EmailVerificationCode as EmailVerificationCodeModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        Mail::fake();

        $response = $this->postJson("/api/v1/auth/register", [
            "name" => "John Doe",
            "email" => "john@example.com",
            "password" => "password123",
            "password_confirmation" => "password123",
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                "user" => ["id", "name", "email", "email_verified", "created_at", "updated_at"],
                "token",
            ])
            ->assertJsonPath("user.name", "John Doe")
            ->assertJsonPath("user.email", "john@example.com")
            ->assertJsonPath("user.email_verified", false);

        $user = User::where("email", "john@example.com")->first();
        $this->assertModelExists($user);

        $verification = EmailVerificationCodeModel::where("user_id", $response->json("user.id"))->first();
        $this->assertModelExists($verification);

        Mail::assertSent(\App\Mail\EmailVerificationCode::class);
    }

    public function test_registration_requires_name(): void
    {
        $response = $this->postJson("/api/v1/auth/register", [
            "email" => "john@example.com",
            "password" => "password123",
            "password_confirmation" => "password123",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(["name"]);
    }

    public function test_registration_requires_valid_email(): void
    {
        $response = $this->postJson("/api/v1/auth/register", [
            "name" => "John Doe",
            "email" => "not-an-email",
            "password" => "password123",
            "password_confirmation" => "password123",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(["email"]);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(["email" => "john@example.com"]);

        $response = $this->postJson("/api/v1/auth/register", [
            "name" => "John Doe",
            "email" => "john@example.com",
            "password" => "password123",
            "password_confirmation" => "password123",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(["email"]);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->postJson("/api/v1/auth/register", [
            "name" => "John Doe",
            "email" => "john@example.com",
            "password" => "password123",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(["password"]);
    }

    public function test_registration_requires_minimum_password_length(): void
    {
        $response = $this->postJson("/api/v1/auth/register", [
            "name" => "John Doe",
            "email" => "john@example.com",
            "password" => "short",
            "password_confirmation" => "short",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(["password"]);
    }

    public function test_registration_does_not_return_password(): void
    {
        Mail::fake();

        $response = $this->postJson("/api/v1/auth/register", [
            "name" => "John Doe",
            "email" => "john@example.com",
            "password" => "password123",
            "password_confirmation" => "password123",
        ]);

        $response->assertStatus(201)
            ->assertJsonMissing(["password"]);
    }
}
