<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationCode;
use App\Models\EmailVerificationCode as EmailVerificationCodeModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_email_with_valid_code(): void
    {
        $user = User::factory()->unverified()->create();

        $verification = EmailVerificationCodeModel::create([
            "user_id" => $user->id,
            "code" => "123456",
            "expires_at" => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/auth/verify-email", [
                "code" => "123456",
            ]);

        $response->assertOk()
            ->assertJsonPath("message", "Email verified successfully.")
            ->assertJsonPath("user.email_verified", true);

        $this->assertNotNull($user->fresh()?->email_verified_at);
        $this->assertModelMissing($verification);
    }

    public function test_verification_fails_with_invalid_code(): void
    {
        $user = User::factory()->unverified()->create();

        EmailVerificationCodeModel::create([
            "user_id" => $user->id,
            "code" => "123456",
            "expires_at" => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/auth/verify-email", [
                "code" => "999999",
            ]);

        $response->assertStatus(422)
            ->assertJsonPath("message", "Invalid or expired verification code.");

        $this->assertNull($user->fresh()?->email_verified_at);
    }

    public function test_verification_fails_with_expired_code(): void
    {
        $user = User::factory()->unverified()->create();

        EmailVerificationCodeModel::create([
            "user_id" => $user->id,
            "code" => "123456",
            "expires_at" => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/auth/verify-email", [
                "code" => "123456",
            ]);

        $response->assertStatus(422)
            ->assertJsonPath("message", "Invalid or expired verification code.");
    }

    public function test_already_verified_user_gets_informative_response(): void
    {
        $user = User::factory()->create(); // verified by default

        $response = $this->actingAs($user)
            ->postJson("/api/v1/auth/verify-email", [
                "code" => "123456",
            ]);

        $response->assertOk()
            ->assertJsonPath("message", "Email already verified.");
    }

    public function test_verification_requires_code(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/v1/auth/verify-email", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(["code"]);
    }

    public function test_verification_requires_6_digit_code(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/v1/auth/verify-email", [
                "code" => "123",
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(["code"]);
    }

    public function test_unauthenticated_user_cannot_verify_email(): void
    {
        $response = $this->postJson("/api/v1/auth/verify-email", [
            "code" => "123456",
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_resend_verification_code(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/v1/auth/resend-verification");

        $response->assertOk()
            ->assertJsonPath("message", "Verification code sent.");

        Mail::assertSent(EmailVerificationCode::class);

        $verification = EmailVerificationCodeModel::where("user_id", $user->id)->first();
        $this->assertModelExists($verification);
    }

    public function test_resend_replaces_old_verification_code(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create();

        $oldVerification = EmailVerificationCodeModel::create([
            "user_id" => $user->id,
            "code" => "111111",
            "expires_at" => now()->addMinutes(15),
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/auth/resend-verification");

        $this->assertModelMissing($oldVerification);
        $this->assertDatabaseCount("email_verification_codes", 1);
    }

    public function test_already_verified_user_cannot_resend_code(): void
    {
        $user = User::factory()->create(); // verified by default

        $response = $this->actingAs($user)
            ->postJson("/api/v1/auth/resend-verification");

        $response->assertOk()
            ->assertJsonPath("message", "Email already verified.");
    }

    public function test_unauthenticated_user_cannot_resend_verification(): void
    {
        $response = $this->postJson("/api/v1/auth/resend-verification");

        $response->assertStatus(401);
    }
}
