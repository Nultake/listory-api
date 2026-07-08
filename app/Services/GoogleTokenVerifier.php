<?php

namespace App\Services;

use Google\Client;

class GoogleTokenVerifier
{
    public function __construct(private readonly Client $client) {}

    /**
     * Verify a Google ID token (JWT) and return the authenticated user.
     *
     * Returns null when the token is invalid, expired, issued for a
     * different audience than the configured client ID, or when Google
     * has not verified ownership of the email address.
     */
    public function verify(string $idToken): ?GoogleUser
    {
        try {
            $payload = $this->client->verifyIdToken($idToken);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload["sub"], $payload["email"])) {
            return null;
        }

        if (($payload["email_verified"] ?? false) !== true) {
            return null;
        }

        return new GoogleUser(
            id: (string) $payload["sub"],
            email: (string) $payload["email"],
            name: isset($payload["name"]) ? (string) $payload["name"] : (string) $payload["email"],
        );
    }
}
