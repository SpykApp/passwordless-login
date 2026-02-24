<?php

namespace SpykApp\PasswordlessLogin\Actions;

use Illuminate\Http\Request;
use SpykApp\PasswordlessLogin\Contracts\BotDetector;
use SpykApp\PasswordlessLogin\Events\BotDetected;

class DefaultBotDetector implements BotDetector
{
    /**
     * Determine if the request appears to be from a bot or prefetch scanner.
     */
    public function isBot(Request $request): bool
    {
        // Check HTTP method - bots often use HEAD/OPTIONS
        if ($this->isBotMethod($request)) {
            BotDetected::dispatch($request, 'bot_http_method', $request->route('token'));
            return true;
        }

        // Check prefetch headers
        if ($this->hasPrefetchHeaders($request)) {
            BotDetected::dispatch($request, 'prefetch_header', $request->route('token'));
            return true;
        }

        // Check user agent
        if ($this->isBotUserAgent($request)) {
            BotDetected::dispatch($request, 'bot_user_agent', $request->route('token'));
            return true;
        }

        // Check for missing or suspicious headers that real browsers always send
        if ($this->hasSuspiciousHeaders($request)) {
            BotDetected::dispatch($request, 'suspicious_headers', $request->route('token'));
            return true;
        }

        return false;
    }

    /**
     * Check if the HTTP method is commonly used by bots.
     */
    protected function isBotMethod(Request $request): bool
    {
        $botMethods = config('passwordless-login.bot_detection.bot_methods', ['HEAD', 'OPTIONS']);

        return in_array(strtoupper($request->method()), $botMethods);
    }

    /**
     * Check for prefetch/preview headers.
     */
    protected function hasPrefetchHeaders(Request $request): bool
    {
        $prefetchHeaders = config('passwordless-login.bot_detection.prefetch_headers', []);

        foreach ($prefetchHeaders as $header => $values) {
            $headerValue = $request->header($header);

            if ($headerValue && in_array(strtolower($headerValue), array_map('strtolower', $values))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user agent matches known bot patterns.
     */
    protected function isBotUserAgent(Request $request): bool
    {
        $userAgent = $request->userAgent() ?? '';
        $patterns = config('passwordless-login.bot_detection.user_agent_patterns', []);

        foreach ($patterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for suspicious header patterns that indicate non-browser requests.
     */
    protected function hasSuspiciousHeaders(Request $request): bool
    {
        // Real browsers send Accept headers with text/html
        $accept = $request->header('Accept', '');

        if (empty($accept) || $accept === '*/*') {
            // Not definitive - some legitimate scenarios have this
            // Only flag if combined with other suspicious signals
            $userAgent = $request->userAgent() ?? '';

            if (empty($userAgent)) {
                return true;
            }
        }

        return false;
    }
}
