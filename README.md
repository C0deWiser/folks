# Folks Laravel Package

## Introduction

Folks provides a beautiful dashboard for users and roles management.

## Installation

You may install Folks into your project using the Composer package manager:

    composer require codewiser/folks

After installing Folks, publish its assets using the `folks:install` Artisan command:

    php artisan folks:install

## Folks Service Provider

The `folks:install` command discussed above will also publish the `App\Providers\FolksServiceProvider` class. You should ensure this class is registered within the `providers` array of your application's `config/app.php` configuration file.

The Folks service provider registers the actions that Folks published and instructs Folks to use them when their respective tasks are executed by Folks.

### Users Configuration

In a `boot` method of Folks service provider you should provide some information about `User` model.

Provide user's builder to fetch users, that are allowed to be seen by currently authenticated user:

```php
Folks::usersBuilder(function (User $user = null) {
    $builder = \App\Models\User::query()->withTrashed();
    
    if (!$user) {
        $builder->whereKey(0);
    } elseif ($user->isNotAdmin()) {
        $builder->whereKey($user->id);
    }
    
    return $builder;
});
```

### UI Configuration

Describe a schema for user's form. Define user control's that will be used by Folks to build user interface:

```php
use Codewiser\Folks\Controls\Input;
use Codewiser\Folks\Controls\Label;

Folks::usersSchema([
    Label::make('id'),
    Input::make('name')->type('text')->required(),
    Input::make('email')->type('email')->required(),
    Label::make('email_verified_at')->cast('boolean')->label('Email Verified')
]);
```

Available user controls are:

#### Label

Immutable control to only show data. Described by `Codewiser\Folks\Controls\Label` class.

You may define `label` and apply `cast` to a value.

#### Input
    
Input control to show and, optionally, edit data. Described by `Codewiser\Folks\Controls\Input` class.

You may define `label` and apply `cast` to a value. Input has default `type=text` and optional `required` and `readonly` attributes.

#### Option List

Control to build choice list. Described by `Codewiser\Folks\Controls\Options` class.

You may define `label` and apply `cast` to a value. Control has optional `required`, `readonly` and `multiple` attributes.

List of available `options` may be defined in a few ways:

* with `Builder`:
    
    Provide `Builder` and, optionally, attributes, that holds caption and value for each option.

      Options::make('role')->multiple()->options(Role::query(), 'name', 'id')

* with `Collection`:

  Provide `Collection` and, optionally, attributes, that holds caption and value for each option.

      Options::make('role')->multiple()->options(Role::all(), 'name', 'id')

* with array of `Codewiser\Folks\Controls\Option`:

      Options::make('role')->multiple()->options([
          Option::make('admin')->label('Super User'),
          Option::make('manager')
      ])

### Dashboard Authorization

Folks exposes a dashboard at the `/folks` URI. By default, you will only be able to access this dashboard in the `local` environment. However, within your `app/Providers/FolksServiceProvider.php` file, there is an authorization gate definition. This authorization gate controls access to Folks in **non-local** environments. You are free to modify this gate as needed to restrict access to your Folks installation:

```php
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
```

#### Application Policies

Gate definition `viewFolks` used only to guard Folks dashboard in general. Any other abilities, such as create, view, update or delete user, controlled by `UserPolicy` of your application.

## Upgrading Folks

When upgrading to any new Folks version, you should re-publish Folks' assets:

    php artisan folks:publish

To keep the assets up-to-date and avoid issues in future updates, you may add the `folks:publish` command to the `post-update-cmd` scripts in your application's `composer.json` file:

```
{
    "scripts": {
        "post-update-cmd": [
            "@php artisan folks:publish --ansi"
        ]
    }
}
```

