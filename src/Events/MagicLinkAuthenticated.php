<?php

namespace SpykApp\PasswordlessLogin\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class MagicLinkAuthenticated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Authenticatable $user,
        public readonly Request $request,
        public readonly ?string $guard = null,
    ) {}
}
