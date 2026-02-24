<?php

namespace SpykApp\PasswordlessLogin;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use SpykApp\PasswordlessLogin\Contracts\AfterLoginAction;
use SpykApp\PasswordlessLogin\Contracts\BotDetector;
use SpykApp\PasswordlessLogin\Contracts\LoginCondition;
use SpykApp\PasswordlessLogin\Contracts\TokenGenerator;
use SpykApp\PasswordlessLogin\Events\MagicLinkAuthenticated;
use SpykApp\PasswordlessLogin\Events\MagicLinkClicked;
use SpykApp\PasswordlessLogin\Events\MagicLinkExpired;
use SpykApp\PasswordlessLogin\Events\MagicLinkFailed;
use SpykApp\PasswordlessLogin\Events\MagicLinkGenerated;
use SpykApp\PasswordlessLogin\Events\MagicLinkSent;
use SpykApp\PasswordlessLogin\Events\MagicLinkThrottled;
use SpykApp\PasswordlessLogin\Events\MagicLinkUsed;
use SpykApp\PasswordlessLogin\Exceptions\InvalidTokenException;
use SpykApp\PasswordlessLogin\Exceptions\LoginConditionFailedException;
use SpykApp\PasswordlessLogin\Exceptions\ThrottleException;
use SpykApp\PasswordlessLogin\Models\MagicLoginToken;
use SpykApp\PasswordlessLogin\Notifications\MagicLinkNotification;

class PasswordlessLoginManager
{
    protected ?Authenticatable $user = null;
    protected ?string $guard = null;
    protected ?string $redirectUrl = null;
    protected ?int $expiryMinutes = null;
    protected ?int $maxUses = null;
    protected ?int $tokenLength = null;
    protected bool $remember = false;
    protected array $metadata = [];
    protected bool $sendNotification = true;
    protected ?string $notificationClass = null;
    protected ?string $mailableClass = null;

    public function __construct(
        protected TokenGenerator $tokenGenerator,
        protected BotDetector $botDetector,
    ) {}

    /**
     * Set the user to generate a magic link for.
     */
    public function forUser(Authenticatable $user): self
    {
        $instance = clone $this;
        $instance->user = $user;
        return $instance;
    }

    /**
     * Set the authentication guard.
     */
    public function guard(?string $guard): self
    {
        $this->guard = $guard;
        return $this;
    }

    /**
     * Set the redirect URL after successful login.
     */
    public function redirectTo(string $url): self
    {
        $this->redirectUrl = $url;
        return $this;
    }

    /**
     * Set the link expiry in minutes.
     */
    public function expiresIn(int $minutes): self
    {
        $this->expiryMinutes = $minutes;
        return $this;
    }

    /**
     * Set how many times the link can be used.
     */
    public function maxUses(?int $uses): self
    {
        $this->maxUses = $uses;
        return $this;
    }

    /**
     * Set whether to remember the login session.
     */
    public function remember(bool $remember = true): self
    {
        $this->remember = $remember;
        return $this;
    }

    /**
     * Set the token length.
     */
    public function tokenLength(int $length): self
    {
        $this->tokenLength = $length;
        return $this;
    }

    /**
     * Attach metadata to the token.
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Disable sending the notification automatically.
     */
    public function withoutNotification(): self
    {
        $this->sendNotification = false;
        return $this;
    }

    /**
     * Use a custom notification class.
     */
    public function useNotification(string $notificationClass): self
    {
        $this->notificationClass = $notificationClass;
        return $this;
    }

    /**
     * Use a custom mailable class.
     */
    public function useMailable(string $mailableClass): self
    {
        $this->mailableClass = $mailableClass;
        return $this;
    }

    /**
     * Generate a magic link and optionally send it.
     *
     * @return array{url: string, token: MagicLoginToken}
     * @throws ThrottleException
     */
    public function generate(?Request $request = null): array
    {
        $this->ensureUserIsSet();

        // Check rate limiting
        $this->checkThrottle();

        // Invalidate previous tokens if configured
        if (config('passwordless-login.security.invalidate_previous', true)) {
            $this->invalidateTokensFor($this->user);
        }

        // Generate token
        $length = $this->tokenLength ?? config('passwordless-login.token.length', 32);
        $plainToken = $this->tokenGenerator->generate($length);
        $hashedToken = $this->tokenGenerator->hash($plainToken);

        // Determine settings
        $expiryMinutes = $this->expiryMinutes ?? config('passwordless-login.expiry_minutes', 15);
        $maxUses = $this->maxUses ?? config('passwordless-login.max_uses', 1);
        $guard = $this->guard ?? config('passwordless-login.guard');

        // Store token
        $tokenModel = MagicLoginToken::create([
            'authenticatable_type' => get_class($this->user),
            'authenticatable_id' => $this->user->getAuthIdentifier(),
            'token' => $hashedToken,
            'guard' => $guard,
            'redirect_url' => $this->redirectUrl,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'max_uses' => $maxUses,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'metadata' => !empty($this->metadata) ? $this->metadata : null,
        ]);

        // Generate URL
        $url = $this->buildUrl($plainToken);

        // Dispatch event
        MagicLinkGenerated::dispatch($this->user, $plainToken, $url, $request?->ip());

        // Send notification if enabled
        if ($this->sendNotification && config('passwordless-login.notification.enabled', true)) {
            $this->sendMagicLinkNotification($url, $expiryMinutes);
        }

        return [
            'url' => $url,
            'token' => $tokenModel,
        ];
    }

    /**
     * Verify and authenticate with a magic link token.
     *
     * @throws InvalidTokenException
     * @throws LoginConditionFailedException
     */
    public function authenticate(string $plainToken, Request $request): Authenticatable
    {
        // Find the token
        $tokenModel = $this->findValidToken($plainToken);

        if (!$tokenModel) {
            MagicLinkFailed::dispatch('invalid_token', $request, $plainToken, $request->ip());
            throw InvalidTokenException::invalid();
        }

        // Dispatch clicked event
        $isBot = $this->botDetector->isBot($request);
        MagicLinkClicked::dispatch($tokenModel, $request, $isBot);

        // Check expiry
        if ($tokenModel->isExpired()) {
            MagicLinkExpired::dispatch($tokenModel, $request);
            throw InvalidTokenException::expired();
        }

        // Check usage
        if ($tokenModel->isFullyUsed()) {
            MagicLinkFailed::dispatch('token_used', $request, $plainToken, $request->ip());
            throw InvalidTokenException::used();
        }

        // Check IP binding
        if (config('passwordless-login.security.ip_binding', false)) {
            if ($tokenModel->ip_address && $tokenModel->ip_address !== $request->ip()) {
                MagicLinkFailed::dispatch('ip_mismatch', $request, $plainToken, $request->ip());
                throw InvalidTokenException::ipMismatch();
            }
        }

        // Check user agent binding
        if (config('passwordless-login.security.user_agent_binding', false)) {
            if ($tokenModel->user_agent && $tokenModel->user_agent !== $request->userAgent()) {
                MagicLinkFailed::dispatch('user_agent_mismatch', $request, $plainToken, $request->ip());
                throw InvalidTokenException::userAgentMismatch();
            }
        }

        // Get the user
        $user = $tokenModel->authenticatable;

        if (!$user) {
            MagicLinkFailed::dispatch('user_not_found', $request, $plainToken, $request->ip());
            throw InvalidTokenException::invalid();
        }

        // Check login conditions
        $this->checkConditions($user);

        // Increment usage
        $tokenModel->incrementUseCount();
        MagicLinkUsed::dispatch($tokenModel, $request);

        // Invalidate all tokens on login if configured
        if (config('passwordless-login.security.invalidate_on_login', true)) {
            $this->invalidateTokensFor($user);
        }

        // Authenticate
        $guard = $tokenModel->guard ?? config('passwordless-login.guard');
        $remember = $this->remember ?: config('passwordless-login.remember', false);

        Auth::guard($guard)->login($user, $remember);

        MagicLinkAuthenticated::dispatch($user, $request, $guard);

        // Run after-login action
        $this->runAfterLoginAction($user, $request);

        return $user;
    }

    /**
     * Get the redirect URL after authentication.
     */
    public function getRedirectUrl(string $plainToken): string
    {
        $tokenModel = $this->findValidToken($plainToken);

        if ($tokenModel?->redirect_url) {
            return $tokenModel->redirect_url;
        }

        $defaultRedirect = config('passwordless-login.redirect.on_success', '/dashboard');
        $isRoute = config('passwordless-login.redirect.on_success_is_route', false);

        return $isRoute ? route($defaultRedirect) : $defaultRedirect;
    }

    /**
     * Determine if bot detection should show a confirmation page.
     */
    public function shouldShowConfirmation(Request $request): bool
    {
        if (!config('passwordless-login.bot_detection.enabled', true)) {
            return false;
        }

        return $this->botDetector->isBot($request);
    }

    /**
     * Find a user by email.
     */
    public function findUserByEmail(string $email): ?Authenticatable
    {
        $model = config('passwordless-login.user_model', \App\Models\User::class);
        $column = config('passwordless-login.email_column', 'email');

        return $model::where($column, $email)->first();
    }

    /**
     * Invalidate all tokens for a user.
     */
    public function invalidateTokensFor(Authenticatable $user): int
    {
        return MagicLoginToken::forAuthenticatable($user)->valid()->delete();
    }

    /**
     * Clean up expired tokens.
     */
    public function cleanupExpiredTokens(): int
    {
        return MagicLoginToken::expired()->delete();
    }

    /**
     * Build the magic link URL.
     */
    protected function buildUrl(string $token): string
    {
        $routeName = config('passwordless-login.route.name', 'passwordless.login');

        return route($routeName, ['token' => $token]);
    }

    /**
     * Find a valid token model by plain token.
     */
    protected function findValidToken(string $plainToken): ?MagicLoginToken
    {
        $hashAlgorithm = config('passwordless-login.token.hash_algorithm', 'sha256');

        if ($hashAlgorithm === 'sha256') {
            $hashedToken = hash('sha256', $plainToken);
            return MagicLoginToken::where('token', $hashedToken)->first();
        }

        // For bcrypt/argon, we need to check all valid tokens (less efficient)
        $tokens = MagicLoginToken::valid()->get();

        foreach ($tokens as $token) {
            if ($this->tokenGenerator->verify($plainToken, $token->token)) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Check rate limiting.
     *
     * @throws ThrottleException
     */
    protected function checkThrottle(): void
    {
        if (!config('passwordless-login.throttle.enabled', true)) {
            return;
        }

        $key = 'passwordless-login:' . get_class($this->user) . ':' . $this->user->getAuthIdentifier();
        $maxAttempts = config('passwordless-login.throttle.max_attempts', 5);
        $decayMinutes = config('passwordless-login.throttle.decay_minutes', 10);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            MagicLinkThrottled::dispatch($this->user, $seconds);
            throw new ThrottleException($seconds);
        }

        RateLimiter::hit($key, $decayMinutes * 60);
    }

    /**
     * Check login conditions.
     *
     * @throws LoginConditionFailedException
     */
    protected function checkConditions(Authenticatable $user): void
    {
        $conditions = config('passwordless-login.conditions', []);

        foreach ($conditions as $condition) {
            if (is_callable($condition)) {
                if (!$condition($user)) {
                    throw new LoginConditionFailedException();
                }
                continue;
            }

            if (is_string($condition)) {
                $instance = app($condition);

                if ($instance instanceof LoginCondition) {
                    if (!$instance->check($user)) {
                        throw new LoginConditionFailedException($instance->message());
                    }
                    continue;
                }

                // If it's an invokable class
                if (method_exists($instance, '__invoke')) {
                    if (!$instance($user)) {
                        throw new LoginConditionFailedException();
                    }
                    continue;
                }
            }
        }
    }

    /**
     * Send the magic link notification or mailable.
     */
    protected function sendMagicLinkNotification(string $url, int $expiryMinutes): void
    {
        // Check for custom mailable
        $mailableClass = $this->mailableClass
            ?? config('passwordless-login.notification.mailable');

        if ($mailableClass) {
            \Illuminate\Support\Facades\Mail::to($this->user)->send(
                new $mailableClass($url, $expiryMinutes)
            );
            MagicLinkSent::dispatch($this->user, 'mailable');
            return;
        }

        // Use notification
        $notificationClass = $this->notificationClass
            ?? config('passwordless-login.notification.class')
            ?? MagicLinkNotification::class;

        $this->user->notify(new $notificationClass($url, $expiryMinutes, $this->metadata));
        MagicLinkSent::dispatch($this->user, 'notification');
    }

    /**
     * Run the after-login action if configured.
     */
    protected function runAfterLoginAction(Authenticatable $user, Request $request): void
    {
        $action = config('passwordless-login.after_login_action');

        if (!$action) {
            return;
        }

        if (is_callable($action)) {
            $action($user, $request);
            return;
        }

        if (is_string($action)) {
            $instance = app($action);

            if ($instance instanceof AfterLoginAction) {
                $instance->execute($user, $request);
                return;
            }

            if (method_exists($instance, '__invoke')) {
                $instance($user, $request);
            }
        }
    }

    /**
     * Ensure a user has been set.
     */
    protected function ensureUserIsSet(): void
    {
        if (!$this->user) {
            throw new \RuntimeException('No user set. Call forUser() first.');
        }
    }
}
