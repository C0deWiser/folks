<?php

namespace Codewiser\Rpac;

use Illuminate\Support\ServiceProvider;
use Codewiser\Rpac\Middleware\VerifyRole;

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

        $this->bootMiddleware();

        $this->registerBladeExtensions();

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\RpacCommand::class,
            ]);
        }
    }

    private function bootMiddleware()
    {
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
            return "<?php if (Auth::check() && Auth::user()->playRole{$expression}): ?>";
        });

        $blade->directive('endrole', function () {
            return "<?php endif; ?>";
        });
    }
}
