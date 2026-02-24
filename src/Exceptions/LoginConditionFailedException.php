<?php

namespace SpykApp\PasswordlessLogin\Exceptions;

use Exception;

class LoginConditionFailedException extends Exception
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            $message ?: __('passwordless-login::messages.error_condition_failed'),
            403
        );
    }
}
