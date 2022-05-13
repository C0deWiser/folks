<?php

namespace App\Providers;

use App\Actions\Folks\CreateNewUser;
use App\Actions\Folks\UpdateUserProfileInformation;
use Codewiser\Folks\Controls\Input;
use Codewiser\Folks\Controls\Label;
use Codewiser\Folks\Controls\Option;
use Codewiser\Folks\Controls\Options;
use Codewiser\Folks\Folks;
use Codewiser\Folks\FolksApplicationServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class FolksServiceProvider extends FolksApplicationServiceProvider
{
    public function boot()
    {
        parent::boot();

        Folks::usersBuilder(function ($user = null) {
            // scope query with only users, that authenticated $user allowed to view!
            return \App\Models\User::query()->withTrashed();
        });

        Folks::usersSchema([
            Label::make('id'),
            Input::make('name')->type('text')->required(),
            Input::make('email')->type('email')->required(),
            Label::make('email_verified_at')->cast('boolean')->label('Email Verified'),
            Label::make('created_at'),
            Label::make('updated_at'),
        ]);

        Folks::createUsersUsing(CreateNewUser::class);
        Folks::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
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
     * Return roles' collection.
     *
     * Every role should conform to \Codewiser\Folks\Contracts\RoleContract
     *
     * Role may be as Model, as Enum.
     *
     * @return Collection
     */
    protected function roles()
    {
        return collect();
    }
}
