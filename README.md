# Roles/Permissions Access Control [RPAC] Laravel Package

## Installation

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
    public function getDefaults($action)
    {
        if ($action == 'view') {
            // Any user may view
            return '*';
        }
    
        // Other actions allowed only to Admin
        return 'Role\Admin';
    }
}
```

### Relationships aka Model Roles

Policy provides you way to define relationships between User and Model. Relationship is a role, that has sense only in some context.

Defining relationship

```php
class PostPolicy extends RpacPolicy
{
    protected $relationships = ['author'];
    
    protected function model()
    {
        return App\Post::class;
    }
    
    public function getDefaults($action)
    {
        if ($action == 'view') {
            // Any user may view
            return '*';
        }

        if ($action == 'update') {
            // Author may edit his post
            return ['Post\Author', 'Role\Admin'];
        }
    
        // Other actions allowed only to Admin
        return 'Role\Admin';
    }
}
```

Defining relationship scope:
```php
class Post extends Model
{
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    public function scopeRelationshipAuthor(Builder $query, $user)
    {
        $query->where('author_id', $user->getKey());
    }
}
```

## Conclusion

Use this Policy as any other Laravel Policy
