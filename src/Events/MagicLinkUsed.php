<?php

namespace SpykApp\PasswordlessLogin\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use SpykApp\PasswordlessLogin\Models\MagicLoginToken;

class MagicLinkUsed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly MagicLoginToken $tokenModel,
        public readonly Request $request,
    ) {}
}
