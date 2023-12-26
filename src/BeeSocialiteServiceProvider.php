<?php

namespace Bee\Socialite;

use App\Models\Setting;
use Bee\Socialite\Enums\SocialDriveEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class BeeSocialiteServiceProvider extends ServiceProvider
{
    const PACKAGE_NAMESPACE = 'bee-socialite';

    public array $bindings = [];

    /**
     * Register services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerOAuthSettings();

        $this->registerMigrations();
        $this->registerResources();

        $this->publishResources();

        $this->configureRoutes();
        $this->registerTranslations();
    }

    /**
     * Configure the routes offered by the application.
     */
    protected function configureRoutes(): void
    {
        Route::group([
            'middleware' => ['web'],
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    private function publishResources(): void
    {
        $this->publishes([
            __DIR__.'/../config/bee-socialite.php' => config_path('bee-socialite.php'),
        ], self::PACKAGE_NAMESPACE);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/bee-socialite'),
        ], self::PACKAGE_NAMESPACE);

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'migrations');
    }

    private function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', self::PACKAGE_NAMESPACE);
    }

    private function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', self::PACKAGE_NAMESPACE);
    }

    public function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/');
    }

    public function registerOAuthSettings(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/bee-socialite.php', self::PACKAGE_NAMESPACE
        );

        // if you use config from env instead of setting database
        if ($this->isUseEnvSettings()) {
            $this->updateServiceSettings(config(self::PACKAGE_NAMESPACE));
            return;
        }

        if (Schema::hasTable('settings')) {
            $settings = Cache::remember('settings', 60, function() {
                return Setting::query()->pluck('value', 'name')->toArray();
            });

            $this->updateServiceSettings($settings);
        }
    }

    protected function updateServiceSettings(array $settings = []): void
    {
        collect($settings)->filter(
            fn($value, $key) => !empty($value)
                && Str::contains($key, array_map(fn($case) => $case->value,SocialDriveEnum::cases()))
        )
            ->each(fn($value, $key) => config(["services.$key" => $value]));
    }

    protected function isUseEnvSettings(): bool
    {
        if (empty(config(self::PACKAGE_NAMESPACE . '.domain_use_env_settings', ''))) {
            return false;
        }

        $whitelistDomains = explode(',', config(self::PACKAGE_NAMESPACE . '.domain_use_env_settings', ''));
        return Str::contains(request()->getHost(), $whitelistDomains);
    }
}
