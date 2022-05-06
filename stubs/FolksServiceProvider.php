<?php

namespace App\Providers;

use App\Actions\Folks\CreateNewUser;
use App\Actions\Folks\UpdateUserProfileInformation;
use Codewiser\Folks\Controls\Label;
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

        Folks::usersClassname(\App\Models\User::class);

        Folks::usersBuilder(function (?Authenticatable $user) {
            // scope query with users, that Authenticatable allowed to view!
            return \App\Models\User::query()->withTrashed();
        });

        Folks::usersSchema([
            'id' => Label::make('Id'),
            'name' => 'string',
            'email' => 'email',
            'email_verified_at' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime'
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
