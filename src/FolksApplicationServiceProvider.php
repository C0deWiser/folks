<?php

namespace Codewiser\Folks;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class FolksApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->authorization();
    }

    /**
     * Configure the Folks authorization services.
     *
     * @return void
     */
    protected function authorization()
    {
        $this->gate();

        Folks::auth(function ($request) {
            return app()->environment('local') ||
                Gate::check('viewFolks', [$request->user()]);
        });
    }

    /**
     * Register the Folks gate.
     *
     * This gate determines who can access Folks in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewFolks', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
