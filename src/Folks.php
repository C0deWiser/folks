<?php

namespace Codewiser\Folks;


use Closure;
use Codewiser\Folks\Contracts\CreatesNewUsers;
use Codewiser\Folks\Contracts\UpdatesUserProfileInformation;
use Codewiser\Folks\Contracts\UserContract;
use Codewiser\Folks\Controls\Label;
use Codewiser\Folks\Controls\UserControl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;

class Folks
{
    /**
     * The callback that should be used to authenticate Folks users.
     */
    public static Closure $authUsing;

    /**
     * The callback that should provide the collection with available roles.
     */
    public static Closure $rolesUsing;

    /**
     * The callback that should provide users builder.
     */
    public static Closure $usersUsing;

    /**
     * Class name for Users Model.
     */
    public static string $usersClass;

    /**
     * @var Collection|UserControl[]|null
     */
    public static ?Collection $usersSchema = null;

    /**
     * Set the callback that should be used to authenticate Folks users.
     */
    public static function auth(Closure $callback): Folks
    {
        static::$authUsing = $callback;

        return new static;
    }

    /**
     * Determine if the given request can access the Folks dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public static function check($request): bool
    {
        return (static::$authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }

    /**
     * Set the callback that should be used to get roles' collection.
     */
    public static function setRoles(Closure $callback): Folks
    {
        static::$rolesUsing = $callback;

        return new static;
    }

    /**
     * Set the callback that should be used to get users' builder.
     */
    public static function usersBuilder(Closure $callback): Folks
    {
        static::$usersUsing = $callback;

        return new static;
    }

    /**
     *
     */
    public static function getUsersBuilder(?Authenticatable $user): Builder
    {
        return call_user_func(static::$usersUsing, $user);
    }

    /**
     * Determine if Folks's published assets are up-to-date.
     *
     * @throws RuntimeException
     */
    public static function assetsAreCurrent(): bool
    {
        $publishedPath = public_path('vendor/folks/mix-manifest.json');

        if (! File::exists($publishedPath)) {
            throw new RuntimeException('Folks assets are not published. Please run: php artisan folks:publish');
        }

        return File::get($publishedPath) === File::get(__DIR__.'/../public/mix-manifest.json');
    }

    /**
     * Get the default JavaScript variables for Folks
     */
    public static function scriptVariables(): array
    {
        return [
            'path' => config('folks.path'),
        ];
    }

    /**
     * Register a class / callback that should be used to create new users.
     *
     * @param  string  $callback
     * @return void
     */
    public static function createUsersUsing(string $callback)
    {
        app()->singleton(CreatesNewUsers::class, $callback);
    }

    /**
     * Register a class / callback that should be used to update user profile information.
     *
     * @param  string  $callback
     * @return void
     */
    public static function updateUserProfileInformationUsing(string $callback)
    {
        app()->singleton(UpdatesUserProfileInformation::class, $callback);
    }

    /**
     * @param string $classname
     */
    public static function usersClassname(string $classname): void
    {
        self::$usersClass = $classname;
    }

    /**
     * @param Collection $usersSchema
     */
    public static function usersSchema(Collection $usersSchema): void
    {
        self::$usersSchema = $usersSchema;
    }
}
