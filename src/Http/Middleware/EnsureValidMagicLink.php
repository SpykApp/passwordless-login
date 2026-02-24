<?php

namespace SpykApp\PasswordlessLogin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureValidMagicLink
{
    /**
     * Handle an incoming request.
     * This middleware can be applied to custom routes that use magic links.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->route('token');

        if (!$token) {
            abort(403, __('passwordless-login::messages.error_invalid'));
        }

        return $next($request);
    }
}
