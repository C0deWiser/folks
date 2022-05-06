<?php

namespace Codewiser\Folks;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

abstract class FolksApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->authorization();
        $this->registerRoles();
    }

    /**
     * Configure the Folks authorization services.
     *
     * @return void
     */
    final protected function authorization()
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
    abstract protected function gate();

    final protected function registerRoles()
    {
        Folks::setRoles(function () {
            return $this->roles();
        });
    }

    /**
     * Return roles' collection.
     *
     * Every role should conform to \Codewiser\Folks\Contracts\RoleContract
     *
     * Role may be as Model, as Enum.
     *
     * @return Collection
     */
    abstract protected function roles();

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
