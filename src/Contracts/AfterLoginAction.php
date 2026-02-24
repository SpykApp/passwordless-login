<?php

namespace SpykApp\PasswordlessLogin\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

interface AfterLoginAction
{
    /**
     * Execute the action after a successful passwordless login.
     */
    public function execute(Authenticatable $user, Request $request): void;
}
