<?php

namespace Codewiser\Folks;

use App\Actions\Folks\CreateNewUser;
use App\Actions\Folks\UpdateUserProfileInformation;
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

        Folks::setUsersBuilder(function ($user = null) {
            return $this->usersBuilder($user);
        });

        Folks::setUsersSchema($this->usersSchema());

        Folks::createUsersUsing(CreateNewUser::class);
        Folks::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
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

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    abstract protected function usersBuilder(?Authenticatable $user): \Illuminate\Contracts\Database\Eloquent\Builder;
    abstract protected function usersSchema(): array;
}
