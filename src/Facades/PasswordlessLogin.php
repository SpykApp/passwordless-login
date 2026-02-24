<?php

namespace SpykApp\PasswordlessLogin\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager forUser(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager guard(?string $guard)
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager redirectTo(string $url)
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager expiresIn(int $minutes)
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager maxUses(?int $uses)
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager remember(bool $remember = true)
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager tokenLength(int $length)
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager withMetadata(array $metadata)
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager withoutNotification()
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager useNotification(string $notificationClass)
 * @method static \SpykApp\PasswordlessLogin\PasswordlessLoginManager useMailable(string $mailableClass)
 * @method static array generate(?\Illuminate\Http\Request $request = null)
 * @method static \Illuminate\Contracts\Auth\Authenticatable authenticate(string $plainToken, \Illuminate\Http\Request $request)
 * @method static ?\Illuminate\Contracts\Auth\Authenticatable findUserByEmail(string $email)
 * @method static int invalidateTokensFor(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static int cleanupExpiredTokens()
 *
 * @see \SpykApp\PasswordlessLogin\PasswordlessLoginManager
 */
class PasswordlessLogin extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SpykApp\PasswordlessLogin\PasswordlessLoginManager::class;
    }
}
