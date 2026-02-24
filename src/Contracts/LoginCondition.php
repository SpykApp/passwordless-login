<?php

namespace SpykApp\PasswordlessLogin\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface LoginCondition
{
    /**
     * Determine if the user is allowed to log in.
     */
    public function check(Authenticatable $user): bool;

    /**
     * Get the failure message if the condition is not met.
     */
    public function message(): string;
}
