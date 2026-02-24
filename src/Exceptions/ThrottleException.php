<?php

namespace SpykApp\PasswordlessLogin\Exceptions;

use Exception;

class ThrottleException extends Exception
{
    public function __construct(
        public readonly int $availableInSeconds,
    ) {
        $minutes = ceil($availableInSeconds / 60);
        parent::__construct(
            __('passwordless-login::messages.error_throttled', ['minutes' => $minutes]),
            429
        );
    }
}
