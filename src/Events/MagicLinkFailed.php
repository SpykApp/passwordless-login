<?php

namespace SpykApp\PasswordlessLogin\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class MagicLinkFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $reason,
        public readonly Request $request,
        public readonly ?string $token = null,
        public readonly ?string $ipAddress = null,
    ) {}
}
