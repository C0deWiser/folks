<?php

namespace Codewiser\Folks;


use Closure;
use Codewiser\Folks\Contracts\AssetProviderContract;
use Codewiser\Folks\Contracts\CreatesNewUsers;
use Codewiser\Folks\Contracts\UpdatesUserProfileInformation;
use Codewiser\Folks\Contracts\UserProviderContract;
use Codewiser\Folks\Controls\UserControl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;

class Folks implements UserProviderContract, AssetProviderContract
{
    /**
     * The callback that should be used to authenticate Folks users.
     */
    protected static Closure $authUsing;

    /**
     * The callback that should provide the collection with available roles.
     */
    protected static Closure $rolesUsing;

    /**
     * The callback that should provide users builder.
     */
    protected static Closure $usersUsing;

    /**
     * @var Collection|UserControl[]|null
     */
    protected static ?Collection $usersSchema = null;

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
     * @param \Illuminate\Http\Request $request
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
    public static function setUsersBuilder(Closure $callback): Folks
    {
        static::$usersUsing = $callback;

        return new static;
    }

    public function assetsAreCurrent(): bool
    {
        $publishedPath = public_path('vendor/folks/mix-manifest.json');

        if (!File::exists($publishedPath)) {
            throw new RuntimeException('Folks assets are not published. Please run: php artisan folks:publish');
        }

        return File::get($publishedPath) === File::get(__DIR__ . '/../public/mix-manifest.json');
    }

    public function scriptVariables(): array
    {
        return [
            'path' => config('folks.path'),
        ];
    }

    /**
     * Register a class / callback that should be used to create new users.
     *
     * @param string $callback
     * @return void
     */
    public static function createUsersUsing(string $callback)
    {
        app()->singleton(CreatesNewUsers::class, $callback);
    }

    /**
     * Register a class / callback that should be used to update user profile information.
     *
     * @param string $callback
     * @return void
     */
    public static function updateUserProfileInformationUsing(string $callback)
    {
        app()->singleton(UpdatesUserProfileInformation::class, $callback);
    }

    /**
     * @param array $usersSchema
     */
    public static function setUsersSchema(array $usersSchema): void
    {
        self::$usersSchema = collect($usersSchema);
    }

    public function className(): string
    {
        return get_class($this->builder(null)->getModel());
    }

    public function builder(?Authenticatable $user): Builder
    {
        return call_user_func(static::$usersUsing, $user);
    }

    public function schema(): Collection
    {
        return self::$usersSchema ?? collect();
    }
}
