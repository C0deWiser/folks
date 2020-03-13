# Roles/Permissions Access Control [RPAC] Laravel Package


## Installation

### Composer

Pull this package in through Composer (file `composer.json`)...

```js
{
    "require": {
        "php": ">=7.2.0",
        "laravel/framework": "^6.*",
        "codewiser/rpac": "dev-master"
    }
}
```

...and run this command inside your terminal.

    composer update
    
OR require this package

    composer require trunow/rpac:dev-master

### Service Provider

Add the package to your application service providers in `config/app.php` file.

```php
'providers' => [
    
    /*
     * Laravel Framework Service Providers...
     */
    Illuminate\Foundation\Providers\ArtisanServiceProvider::class,
    Illuminate\Auth\AuthServiceProvider::class,
    ...
    
    /**
     * Third Party Service Providers...
     */
    Codewiser\Rpac\RpacServiceProvider::class,

],
```

### Config File And Migrations

Publish the package config file and migrations to your application. Run these commands inside your terminal.

    php artisan vendor:publish --provider="Codewiser\Rpac\RpacServiceProvider"

And also run migrations.

    php artisan migrate

> This uses the default users table which is in Laravel. You should already have the migration file for the users table available and migrated.

### Roles Trait and $with = [roles]

Include `Roles` trait inside your `User` model.

```php
class User extends Model implements AuthenticatableContract
{
    use Authenticatable, Roles;
```

And set protected property $with = ['roles'] (for autoloading roles with User's model).

    protected $with = ['roles'];
    
### Create Su/Admin User

Run command, example `rpac su:1` or `rpac admin:email@example.com` or `role:user@example.com:pa$$w0r5` .

    php artisan rpac su:slava@trunov.me

> (:
>
> And go to `your-domain.com/admin-rpac`
>
> :)


## Usage

### Creating Policy

Create policy class extends `RpacPolicy` for your model.

```php
namespace App\Policies;

use Codewiser\Rpac\Policies\RpacPolicy;

class PostPolicy extends RpacPolicy
{
    protected function model()
    {
        return App\Post::class;
    }
}
```

You may define default rules.

```php
class PostPolicy extends RpacPolicy
{
    protected function model()
    {
        return App\Post::class;
    }
    public function getDefault($action, $role)
    {
        // Admin has full access
        if ($role == 'Role\Admin') {
            return true;
        }
        // Other rules are not defined and may be set in administrative interface.
        return null;
    }
}
```




### Creating Roles

```php
use Codewiser\Rpac\Role;

$adminRole = Role::create([
    'name' => 'Admin',
    'slug' => 'admin',
    'description' => '', // optional
]);
```

### Attaching And Detaching Roles

It's standart. There is `BelongsToMany` relationship between `User` and `Role` model.

```php
use App\User;

$user = User::find($id);
$user->roles()->attach($adminRole); // you can pass whole object, or just an id
```

```php
$user->roles()->detach($adminRole); // in case you want to detach role
$user->roles()->detach(); // in case you want to detach all roles
```

### Checking For Roles

You can now check if the user has required role.

```php
if ($user->is('admin')) { // pass role slug here
    // ...
}
```

You can also do this:

```php
if ($user->isAdmin()) {
    //
}
```

And of course, there is a way to check for multiple roles:

```php
if ($user->is(['admin', 'moderator'])) { 
    /*
    | It is same as:
    | $user->isOr(['admin', 'moderator'])
    */

    // if user has at least one role
}

if ($user->is(['admin', 'moderator'], true)) {
    /*
    | Or alternatively:
    | $user->isAnd(['admin', 'moderator'])
    */

    // if user has all roles
}
```

### Creating Permissions

It's very simple thanks to `Permission` model.

```php
use Codewiser\Rpac\Permission;

$createPostPermission = Permission::create([
    'name' => 'Create posts',
    'entity' => 'App\Post',
    'action' => 'create',
]);
```

### Attaching And Detaching Permissions

You can attach permissions to a role (and of course detach them as well).

```php
use Codewiser\Rpac\Role;

$role = Role::find($roleId);
$role->permissions()->attach($createPostPermission); // permission attached to a role
```

```php
$role->permissions()->detach($createPostPermission); // in case you want to detach permission
$role->permissions()->detach(); // in case you want to detach all permissions
```

### Checking For Permissions

TODO

### Entity Check

Let's say you have an article and you want to edit it.

```php
use App\Article;
use Codewiser\Rpac\Permission;

$editArticlesPermission = Permission::create([
    'name' => 'Edit articles',
    'entity' => 'App\Article',
    'action' => 'edit',
]);

$user->roles()->first()->permissions()->attach($editArticlesPermission);

$article = Article::find(1);

if ($user->can('edit', $article)) { 
    //
}
```

### Blade Extensions

There are four Blade extensions. Basically, it is replacement for classic if statements.

```php
@role('admin') // @if(Auth::check() && Auth::user()->is('admin'))
    // user is admin
@endrole
```

### Middleware

This package comes with `VerifyRole` middleware. You can easily protect your routes.

```php
$router->get('/example', [
    'as' => 'example',
    'middleware' => 'role:admin,manager',
    'uses' => 'ExampleController@index',
]);
```
## Config File

You can change connection for models, models path and there is also a handy pretend feature. Have a look at config file for more information.

## License

This package is free software distributed under the terms of the MIT license.
