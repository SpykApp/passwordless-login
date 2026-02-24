<?php

namespace SpykApp\PasswordlessLogin\Actions;

use SpykApp\PasswordlessLogin\Contracts\TokenGenerator;

class DefaultTokenGenerator implements TokenGenerator
{
    public function __construct(
        protected string $hashAlgorithm = 'sha256',
    ) {}

    /**
     * Generate a cryptographically secure random token.
     */
    public function generate(int $length): string
    {
        return bin2hex(random_bytes(max(16, min(128, $length))));
    }

    /**
     * Hash a token for secure storage.
     */
    public function hash(string $token): string
    {
        return match ($this->hashAlgorithm) {
            'bcrypt' => password_hash($token, PASSWORD_BCRYPT),
            'argon2' => password_hash($token, PASSWORD_ARGON2ID),
            default => hash('sha256', $token),
        };
    }

    /**
     * Verify a plain token against a hashed token.
     */
    public function verify(string $plainToken, string $hashedToken): bool
    {
        return match ($this->hashAlgorithm) {
            'bcrypt', 'argon2' => password_verify($plainToken, $hashedToken),
            default => hash_equals(hash('sha256', $plainToken), $hashedToken),
        };
    }
}
