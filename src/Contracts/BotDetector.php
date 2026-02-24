<?php

namespace SpykApp\PasswordlessLogin\Contracts;

use Illuminate\Http\Request;

interface BotDetector
{
    /**
     * Determine if the request appears to be from a bot or prefetch scanner.
     */
    public function isBot(Request $request): bool;
}
