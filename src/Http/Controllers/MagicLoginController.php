<?php

namespace SpykApp\PasswordlessLogin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SpykApp\PasswordlessLogin\Exceptions\InvalidTokenException;
use SpykApp\PasswordlessLogin\Exceptions\LoginConditionFailedException;
use SpykApp\PasswordlessLogin\PasswordlessLoginManager;

class MagicLoginController extends Controller
{
    public function __construct(
        protected PasswordlessLoginManager $manager,
    ) {}

    /**
     * Handle the magic link click.
     */
    public function __invoke(Request $request, string $token)
    {
        // Bot detection - show confirmation page if needed
        if ($this->shouldShowConfirmation($request)) {
            return $this->showConfirmationPage($request, $token);
        }

        // Handle the confirmed parameter (user clicked the confirmation button)
        return $this->processLogin($request, $token);
    }

    /**
     * Show the confirmation page (for bot detection).
     */
    public function confirm(Request $request, string $token)
    {
        return $this->processLogin($request, $token);
    }

    /**
     * Process the actual login.
     */
    protected function processLogin(Request $request, string $token)
    {
        try {
            $user = $this->manager->authenticate($token, $request);

            $redirectUrl = $this->manager->getRedirectUrl($token);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('passwordless-login::messages.login_success'),
                    'redirect' => $redirectUrl,
                    'user' => $user->toArray(),
                ]);
            }

            return redirect($redirectUrl)->with(
                'status',
                __('passwordless-login::messages.login_success')
            );

        } catch (InvalidTokenException $e) {
            return $this->handleFailure($request, $e->getMessage(), $e->getCode());
        } catch (LoginConditionFailedException $e) {
            return $this->handleFailure($request, $e->getMessage(), $e->getCode());
        } catch (\Throwable $e) {
            report($e);
            return $this->handleFailure(
                $request,
                __('passwordless-login::messages.error_generic'),
                500
            );
        }
    }

    /**
     * Determine if a confirmation page should be shown.
     */
    protected function shouldShowConfirmation(Request $request): bool
    {
        // If 'confirmed' parameter is present, skip confirmation
        if ($request->has('confirmed')) {
            return false;
        }

        if (!config('passwordless-login.bot_detection.enabled', true)) {
            return false;
        }

        $strategy = config('passwordless-login.bot_detection.strategy', 'both');

        // For 'javascript' strategy, always show (let JS handle it)
        if ($strategy === 'javascript') {
            return true;
        }

        // For 'confirmation_page' or 'both', show if bot detected or always
        // We show to all for 'both' strategy since bots can't execute JS
        if ($strategy === 'both') {
            return true;
        }

        // For 'confirmation_page', only show if bot detected
        return $this->manager->shouldShowConfirmation($request);
    }

    /**
     * Show the confirmation page.
     */
    protected function showConfirmationPage(Request $request, string $token)
    {
        $strategy = config('passwordless-login.bot_detection.strategy', 'both');
        $routeName = config('passwordless-login.route.name', 'passwordless.login');

        $confirmUrl = route($routeName, ['token' => $token, 'confirmed' => 1]);

        $view = config('passwordless-login.views.confirmation', 'passwordless-login::confirmation');

        return response()->view($view, [
            'confirmUrl' => $confirmUrl,
            'autoRedirect' => in_array($strategy, ['javascript', 'both']),
            'token' => $token,
        ]);
    }

    /**
     * Handle authentication failure.
     */
    protected function handleFailure(Request $request, string $message, int $code)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => true,
            ], $code >= 400 ? $code : 422);
        }

        $errorView = config('passwordless-login.views.error');

        if ($errorView) {
            return response()->view($errorView, [
                'message' => $message,
                'code' => $code,
            ], $code >= 400 ? $code : 422);
        }

        $failureRedirect = config('passwordless-login.redirect.on_failure', '/login');
        $isRoute = config('passwordless-login.redirect.on_failure_is_route', false);
        $redirect = $isRoute ? route($failureRedirect) : $failureRedirect;

        return redirect($redirect)->with('error', $message);
    }
}
