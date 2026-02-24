<?php

namespace SpykApp\PasswordlessLogin\Contracts;

interface TokenGenerator
{
    /**
     * Generate a random token string.
     */
    public function generate(int $length): string;

    /**
     * Hash a token for secure storage.
     */
    public function hash(string $token): string;

    /**
     * Verify a plain token against a hashed token.
     */
    public function verify(string $plainToken, string $hashedToken): bool;
}
