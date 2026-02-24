<?php

namespace SpykApp\PasswordlessLogin;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SpykApp\PasswordlessLogin\Actions\DefaultBotDetector;
use SpykApp\PasswordlessLogin\Actions\DefaultTokenGenerator;
use SpykApp\PasswordlessLogin\Contracts\BotDetector;
use SpykApp\PasswordlessLogin\Contracts\TokenGenerator;
use SpykApp\PasswordlessLogin\Http\Controllers\MagicLoginController;

class PasswordlessLoginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/passwordless-login.php',
            'passwordless-login'
        );

        // Bind contracts
        $this->app->bind(TokenGenerator::class, function ($app) {
            return new DefaultTokenGenerator(
                config('passwordless-login.token.hash_algorithm', 'sha256')
            );
        });

        $this->app->bind(BotDetector::class, DefaultBotDetector::class);

        // Register the manager as a singleton
        $this->app->singleton(PasswordlessLoginManager::class, function ($app) {
            return new PasswordlessLoginManager(
                $app->make(TokenGenerator::class),
                $app->make(BotDetector::class),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerRoutes();
        $this->loadTranslations();
        $this->loadViews();
        $this->registerScheduledTasks();
    }

    /**
     * Register publishable resources.
     */
    protected function registerPublishables(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        // Config
        $this->publishes([
            __DIR__ . '/../config/passwordless-login.php' => config_path('passwordless-login.php'),
        ], 'passwordless-login-config');

        // Migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/create_passwordless_login_tokens_table.php.stub' =>
                database_path('migrations/' . date('Y_m_d_His') . '_create_passwordless_login_tokens_table.php'),
        ], 'passwordless-login-migrations');

        // Views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/passwordless-login'),
        ], 'passwordless-login-views');

        // Language files
        $this->publishes([
            __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/passwordless-login'),
        ], 'passwordless-login-lang');

        // Publish everything
        $this->publishes([
            __DIR__ . '/../config/passwordless-login.php' => config_path('passwordless-login.php'),
            __DIR__ . '/../database/migrations/create_passwordless_login_tokens_table.php.stub' =>
                database_path('migrations/' . date('Y_m_d_His') . '_create_passwordless_login_tokens_table.php'),
            __DIR__ . '/../resources/views' => resource_path('views/vendor/passwordless-login'),
            __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/passwordless-login'),
        ], 'passwordless-login');
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        $routeConfig = config('passwordless-login.route', []);

        $path = $routeConfig['path'] ?? '/magic-login/{token}';
        $name = $routeConfig['name'] ?? 'passwordless.login';
        $middleware = $routeConfig['middleware'] ?? ['web', 'guest'];
        $prefix = $routeConfig['prefix'] ?? '';

        Route::group([
            'middleware' => $middleware,
            'prefix' => $prefix,
        ], function () use ($path, $name) {
            Route::get($path, MagicLoginController::class)->name($name);
        });
    }

    /**
     * Load translations.
     */
    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../resources/lang',
            'passwordless-login'
        );
    }

    /**
     * Load views.
     */
    protected function loadViews(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'passwordless-login'
        );
    }

    /**
     * Register scheduled cleanup tasks.
     */
    protected function registerScheduledTasks(): void
    {
        if (!config('passwordless-login.cleanup.enabled', true)) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $cron = config('passwordless-login.cleanup.schedule', 'daily');

            $task = $schedule->call(function () {
                app(PasswordlessLoginManager::class)->cleanupExpiredTokens();
            });

            match ($cron) {
                'hourly' => $task->hourly(),
                'daily' => $task->daily(),
                'weekly' => $task->weekly(),
                default => $task->cron($cron),
            };
        });
    }
}
