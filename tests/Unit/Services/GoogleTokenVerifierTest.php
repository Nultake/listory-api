<?php

namespace Tests\Unit\Services;

use App\Services\GoogleTokenVerifier;
use Google\Client;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GoogleTokenVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @param  array<string, mixed>|false  $payload
     */
    private function makeVerifier(array|false $payload): GoogleTokenVerifier
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive("verifyIdToken")
            ->with("id-token")
            ->andReturn($payload);

        return new GoogleTokenVerifier($client);
    }

    public function test_returns_google_user_for_valid_payload(): void
    {
        $verifier = $this->makeVerifier([
            "sub" => "google-123",
            "email" => "john@example.com",
            "email_verified" => true,
            "name" => "John Doe",
        ]);

        $googleUser = $verifier->verify("id-token");

        $this->assertNotNull($googleUser);
        $this->assertSame("google-123", $googleUser->id);
        $this->assertSame("john@example.com", $googleUser->email);
        $this->assertSame("John Doe", $googleUser->name);
    }

    public function test_returns_null_when_email_is_not_verified(): void
    {
        $verifier = $this->makeVerifier([
            "sub" => "google-123",
            "email" => "john@example.com",
            "email_verified" => false,
            "name" => "John Doe",
        ]);

        $this->assertNull($verifier->verify("id-token"));
    }

    public function test_returns_null_when_email_verified_claim_is_missing(): void
    {
        $verifier = $this->makeVerifier([
            "sub" => "google-123",
            "email" => "john@example.com",
            "name" => "John Doe",
        ]);

        $this->assertNull($verifier->verify("id-token"));
    }

    public function test_returns_null_for_invalid_token(): void
    {
        $verifier = $this->makeVerifier(false);

        $this->assertNull($verifier->verify("id-token"));
    }

    public function test_falls_back_to_email_when_name_is_missing(): void
    {
        $verifier = $this->makeVerifier([
            "sub" => "google-123",
            "email" => "john@example.com",
            "email_verified" => true,
        ]);

        $googleUser = $verifier->verify("id-token");

        $this->assertNotNull($googleUser);
        $this->assertSame("john@example.com", $googleUser->name);
    }
}
