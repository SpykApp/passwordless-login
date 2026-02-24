<?php

namespace SpykApp\PasswordlessLogin\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MagicLinkGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Authenticatable $user,
        public readonly string $token,
        public readonly string $url,
        public readonly ?string $ipAddress = null,
    ) {}
}
