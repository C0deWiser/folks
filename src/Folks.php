<?php

namespace Codewiser\Folks;


use Closure;
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
}
