<?php

namespace App\Providers;

use Codewiser\Folks\Controls\Input;
use Codewiser\Folks\Controls\Label;
use Codewiser\Folks\Controls\Options;
use Codewiser\Folks\Controls\Option;
use Codewiser\Folks\FolksApplicationServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class FolksServiceProvider extends FolksApplicationServiceProvider
{
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

    protected function usersBuilder(?Authenticatable $user): Builder
    {
        return \App\Models\User::query()->withTrashed();
    }

    protected function usersSchema(): array
    {
        return [
            Label::make('id'),
            Input::make('name')->type('text')->required(),
            Input::make('email')->type('email')->required(),
            Label::make('email_verified_at')->cast('boolean')->label('Email Verified'),
            Label::make('created_at'),
            Label::make('updated_at'),
        ];
    }
}
