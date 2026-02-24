<?php

namespace SpykApp\PasswordlessLogin\Exceptions;

use Exception;

class InvalidTokenException extends Exception
{
    public static function expired(): self
    {
        return new self(__('passwordless-login::messages.error_expired'), 410);
    }

    public static function invalid(): self
    {
        return new self(__('passwordless-login::messages.error_invalid'), 403);
    }

    public static function used(): self
    {
        return new self(__('passwordless-login::messages.error_used'), 403);
    }

    public static function ipMismatch(): self
    {
        return new self(__('passwordless-login::messages.error_ip_mismatch'), 403);
    }

    public static function userAgentMismatch(): self
    {
        return new self(__('passwordless-login::messages.error_user_agent_mismatch'), 403);
    }
}
