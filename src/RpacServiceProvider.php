<?php

namespace Trunow\Rpac;

use Illuminate\Support\ServiceProvider;
use Trunow\Rpac\Middleware\VerifyRole;

class RpacServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/rpac.php' => config_path('rpac.php')
        ], 'rpac-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => base_path('/database/migrations')
        ], 'rpac-migrations');


        $this->publishes([
            __DIR__.'/../public/vendor/rpac' => public_path('vendor/rpac'),
        ], 'rpac-assets');

        $this->registerBladeExtensions();

        $this->loadRoutesFrom(__DIR__.'/../routes/rpac.php');

        $this->loadViewsFrom(
            __DIR__.'/../resources/views', 'rpac'
        );

        $this->bootMiddleware();
    }

    private function bootMiddleware()
    {
        /** @var Router $router */
        $this->app['router']->aliasMiddleware('role', VerifyRole::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/rpac.php', 'rpac');
    }

    /**
     * Register Blade extensions.
     *
     * @return void
     */
    protected function registerBladeExtensions()
    {
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->directive('role', function ($expression) {
            return "<?php if (Auth::check() && Auth::user()->is{$expression}): ?>";
        });

        $blade->directive('endrole', function () {
            return "<?php endif; ?>";
        });

        /* TODO
        $blade->directive('permission', function ($expression) {
            return "<?php if (Auth::check() && Auth::user()->can{$expression}): ?>";
        });

        $blade->directive('endpermission', function () {
            return "<?php endif; ?>";
        });
        */

    }
}
