<?php

namespace SpykApp\PasswordlessLogin\Traits;

use Illuminate\Http\Request;
use SpykApp\PasswordlessLogin\Facades\PasswordlessLogin;
use SpykApp\PasswordlessLogin\Models\MagicLoginToken;

trait HasMagicLogin
{
    /**
     * Generate a magic login link for this user.
     *
     * @return array{url: string, token: MagicLoginToken}
     */
    public function generateMagicLink(?Request $request = null, array $options = []): array
    {
        $builder = PasswordlessLogin::forUser($this);

        if (isset($options['guard'])) {
            $builder->guard($options['guard']);
        }

        if (isset($options['redirect_url'])) {
            $builder->redirectTo($options['redirect_url']);
        }

        if (isset($options['expiry_minutes'])) {
            $builder->expiresIn($options['expiry_minutes']);
        }

        if (isset($options['max_uses'])) {
            $builder->maxUses($options['max_uses']);
        }

        if (isset($options['remember'])) {
            $builder->remember($options['remember']);
        }

        if (isset($options['token_length'])) {
            $builder->tokenLength($options['token_length']);
        }

        if (isset($options['metadata'])) {
            $builder->withMetadata($options['metadata']);
        }

        if (isset($options['send_notification']) && !$options['send_notification']) {
            $builder->withoutNotification();
        }

        if (isset($options['notification_class'])) {
            $builder->useNotification($options['notification_class']);
        }

        if (isset($options['mailable_class'])) {
            $builder->useMailable($options['mailable_class']);
        }

        return $builder->generate($request);
    }

    /**
     * Send a magic login link to this user.
     *
     * @return array{url: string, token: MagicLoginToken}
     */
    public function sendMagicLink(?Request $request = null, array $options = []): array
    {
        return $this->generateMagicLink($request, $options);
    }

    /**
     * Get all active magic login tokens for this user.
     */
    public function activeMagicTokens()
    {
        return MagicLoginToken::forAuthenticatable($this)->valid()->get();
    }

    /**
     * Invalidate all active magic login tokens for this user.
     */
    public function invalidateMagicTokens(): int
    {
        return PasswordlessLogin::invalidateTokensFor($this);
    }

    /**
     * Get the guard name for passwordless login.
     */
    public function getPasswordlessGuard(): ?string
    {
        return $this->passwordless_guard ?? config('passwordless-login.guard');
    }

    /**
     * Get the redirect URL after passwordless login.
     */
    public function getPasswordlessRedirectUrl(): ?string
    {
        return $this->passwordless_redirect_url ?? null;
    }

    /**
     * Get the magic link expiry in minutes.
     */
    public function getPasswordlessExpiryMinutes(): int
    {
        return $this->passwordless_expiry_minutes ?? config('passwordless-login.expiry_minutes', 15);
    }
}
