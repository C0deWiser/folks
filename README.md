# Roles/Permissions Access Control [R.P.A.C.] Laravel Package

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
    
For now User has `roles()` relation.
    
### Create Admin User

Run command, example `rpac admin:1` or `rpac admin:email@example.com` or `admin:user@example.com:pa$$w0r5` .

    php artisan rpac admin:email@example.com

> (:
>
> Install the [RPAC-UI](https://github.com/C0deWiser/rpacui)
> ...and go to `your-domain.com/rpac-ui`
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
    
}
```

You may define default rules.

```php
class PostPolicy extends RpacPolicy
{
    public function permissions($action)
    {
        if ($action == 'view') {
            // Any user may view
            return '*';
        }
    
        // Other actions allowed only to Admin
        return 'admin';
    }
}
```

When you define default rules, you should return roles, allowed to perform given action.
You may return array of roles, one role, `*` as any role or nothing.
Role `guest` means anonymous user. Role `any` means any authorized user. 

Out-of-the-box Policy supports Laravel default actions: `viewAny` and `create` as non-model 
and `view`, `update`, `delete`, `restore` and `forceDelete` as model actions.

You may define any custom actions.

```php
class PostPolicy extends RpacPolicy
{
    public function engage(?User $user, Model $model)
    {
        return $this->authorize('engage', $user, $model);
    }
    
    public function archive(?User $user)
    {
        return $this->authorize('archive', $user);
    }
}
```

### Policy Pseudo-name

As Laravel supposed, you may use one Policy to few Models. 
RPAC isolate Policies permissions using Policy pseudo-name as a namespace.

For example, your application has few classes, that conforms similar rules and permissions: 
`App\Models\Settings\Categories` and `App\Models\Settings\Tags`.

By default, Policy pseudo-name is a name of Policy class without words `Policies` and `Policy`.
For `App\Models\Policies\SettingsPolicy` pseudo-name will be `App\Models\Settings`.

So, we may define one `SettingsPolicy` and apply it both to `App\Models\Settings\Categories` and `App\Models\Settings\Tags`.
From RPAC point of view they both known as `App\Models\Settings`.

You may override Policy pseudo-name.

```php
class SettingsPolicy extends RpacPolicy
{
    public function getNamespace()
    {
        return 'Settings';
    }
```

### Relationships aka Model Roles

RPAC provides you way to define relationships between User and Model. 
Relationship is a role, that has sense only in context of current Model.

For every defined relationship, Model must provide User-relation method.
Relation may be as single, as plural.

```php
class Post extends Model
{
    use RPAC;

    public $relationships = ['author', 'manager', 'snake_relationship'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // relation is plural
    public function managers()
    {
        return $this->belongsToMany(User::class, 'post_managers');
    }

    // snake_style (or slug-style, or any other) names converted to camelCase
    public function snakeRelationships()
    {
        return $this->hasMany(User::class);
    }
}
```

Only `hasOne`, `hasMany`, `belongsTo` and `belongsToMany` relations supported.

For other types of relationships you have to define scopes.

```php
class Post extends Model
{
    use RPAC;

    public $relationships = ['master'];

    public function scopeOnlyRelatedToMaster(Builder $query, User $user)
    {
        retrun $query->where('Some perverted clousures');
    }
}
```

Relation works faster, but scope is more precise and flexible. You may clause additional statements.

### Relationship names

Defining default rules, you may return not only roles, but relationships too. 
They should be namespaced by Policy pseudo-name.

```php
class PostPolicy extends RpacPolicy
{    
    public function defaults($action)
    {
        if ($action == 'view') {
            // Any user may view
            return 'any';
        }

        if ($action == 'update') {
            // Author may edit his post
            return ['Post\Author', 'admin'];
        }
    
        // Other actions allowed only to Admin
        return 'admin';
    }
}
```

You may get full relationship name user helper method:

```php
Post::getRelationshipQualifiedName('author');
// Post\Author
```

### Examine relationship

```php
if ($user->relatesTo($post, 'author')) {}
// or
if ($post->relatedTo($user, 'author')) {}

```

## Scopes

With relationship you may scope your Model to get only those records, that User may interact.

For example: any user may create post, but user can edit only posts he wrote. In other words, only author can edit posts.
So, the scope will contain only posts with `post.author_id=user.id`

```php
// Only records User may edit
$posts = Post::query()->onlyAllowedTo('update', Auth::user())->get();
```

Also there is a scope to get records related to the user.

```php
// Only records where User is author
$posts = Post::query()->onlyRelated('author', Auth::user())->get();
```

Here is an example of the PostController.

```php
class PostController
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::query()
        	->onlyAllowedTo('view', Auth::user())
        	->onlyRelated('author', Auth::user());

        // return posts to frontend
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        // The current user can update the blog post...
    }
}
```


## Getting abilities

To build proper User Interface you need to know whether User allowed to create or edit Model.
You may collect full list of authorized actions through Model.

```php
$post = Post::find($id);

// Model actions
$abilities = $post->getAuthorizedActions(Auth::user());

// or use property, that returns actions for authorized user
$abilities = $post->authorizedActions;

// [view, update]

// Non-model actions
$abilities = Post::authorizedActions(Auth::user());

// [viewAny]

```
