<?php

namespace Codewiser\Folks;

use Codewiser\Folks\Console\InstallCommand;
use Codewiser\Folks\Console\PublishCommand;
use Codewiser\Folks\Contracts\AssetProviderContract;
use Codewiser\Folks\Contracts\UserProviderContract;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FolksServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();
        $this->registerResources();
        $this->defineAssetPublishing();
        $this->offerPublishing();
        $this->registerCommands();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (!defined('FOLKS_PATH')) {
            define('FOLKS_PATH', realpath(__DIR__ . '/../'));
        }

        $this->mergeConfigFrom(__DIR__ . '/../config/folks.php', 'folks');

        $this->app->singleton(UserProviderContract::class, function () {
            return new Folks();
        });

        $this->app->singleton(AssetProviderContract::class, function () {
            return new Folks();
        });
    }

    /**
     * Define the asset publishing configuration.
     *
     * @return void
     */
    protected function defineAssetPublishing()
    {
        $this->publishes([
            FOLKS_PATH . '/public' => public_path('vendor/folks'),
        ], ['folks-assets', 'laravel-assets']);
    }

    /**
     * Setup the resource publishing groups for Folks
     *
     * @return void
     */
    protected function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/FolksServiceProvider.php' => app_path('Providers/FolksServiceProvider.php'),
            ], 'folks-provider');

            $this->publishes([
                __DIR__.'/../config/folks.php' => config_path('folks.php'),
            ], 'folks-config');

            $this->publishes([
                __DIR__.'/../stubs/CreateNewUser.php' => app_path('Actions/Folks/CreateNewUser.php'),
                __DIR__.'/../stubs/UpdateUserProfileInformation.php' => app_path('Actions/Folks/UpdateUserProfileInformation.php'),
            ], 'folks-support');
        }
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {

            $commands = [
                InstallCommand::class,
                PublishCommand::class
            ];

            $this->commands($commands);
        }
    }

    /**
     * Register the Folks routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group([
            'domain' => config('folks.domain', null),
            'prefix' => config('folks.path'),
            'middleware' => config('folks.middleware', 'web'),
            'as' => 'folks.',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register the Folks resources.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'folks');
    }
}
