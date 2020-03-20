<?php

namespace Codewiser\Rpac\Policies;

use Codewiser\Rpac\Helpers\ReflectionHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;
use Codewiser\Rpac\Permission;
use Codewiser\Rpac\Traits\Roles;

abstract class RpacPolicy
{
    use HandlesAuthorization;

    const BuiltInNamespace = 'Core';
    const RoleNamespace = 'Role';

    /**
     * Set of relationships (aka Model Roles) between User and Model
     * @var array
     * @example ['owner', 'manager']
     */
    protected $relationships = [];

    /**
     * Per-hit data storage
     * @var array
     */
    private $cache = [];

    /**
     * Prepend namespace to string or array of strings
     * @param $namespace
     * @param $data
     * @return array|string
     */
    protected function applyNamespace($namespace, $data)
    {
        if (is_array($data)) {
            return array_map(function ($n) use ($namespace) {
                return $this->applyNamespace($namespace, $n);
            }, $data);
        } else {
            return $namespace . '\\' . Str::studly($data);
        }
    }

    /**
     * The Policy pseudo-name for use in permission table
     * @return string
     */
    public function getPolicyNamespace()
    {
        // Will use Policy::class name without Policy word
        // App\Policies\PostPolicy
        // ->
        // App\Post

        $class = get_class($this);
        $class = Str::replaceLast('Policies\\', '', $class);
        $class = Str::replaceLast('Policy', '', $class);
        return $class;
    }

    /**
     * Policy need to know Model::class it works with
     * @return string
     */
    abstract public function model();

    /**
     * @return array
     */
    public function getModelRoles(): array
    {
        return $this->relationships;
    }

    /**
     * Default (built-in) permissions
     * @param string $action
     * @return array|string|null|void return namespaced(!) roles, allowed to $action
     */
    public function getDefaults($action)
    {
    }

    public function viewAny(?User $user)
    {
        $this->applyScope('view', $user);
        return $this->authorize('viewAny', $user);
    }

    public function view(?User $user, Model $model)
    {
        return $this->authorize('view', $user, $model);
    }

    public function create(?User $user)
    {
        return $this->authorize('create', $user);
    }

    public function update(?User $user, Model $model)
    {
        return $this->authorize('update', $user, $model);
    }

    public function delete(?User $user, Model $model)
    {
        return $this->authorize('delete', $user, $model);
    }

    public function restore(?User $user, Model $model)
    {
        return $this->authorize('restore', $user, $model);
    }

    public function forceDelete(?User $user, Model $model)
    {
        return $this->authorize('forceDelete', $user, $model);
    }

    /**
     * Get method name to scope model with given model-role
     * @param $role
     * @return string
     */
    protected function getScopeName($role)
    {
        $scopeName = 'relationship' . Str::studly($role); // relationshipManager()
        return $scopeName;
//        $methodName = 'scope' . Str::studly($scopeName); // scopeRelationshipManager()
//        return method_exists($this->model(), $methodName) ? $scopeName : null;
    }

    /**
     * Apply global scope to the Model, so user can get only records he allowed to $action
     * @param string $action
     * @param User|null $user
     */
    protected function applyScope($action, ?User $user)
    {
        // if one of user's concrete Roles is permitted to $action, user has access to all records
        // if not:
        // we get all positive permissions with Model+Action signature
        // we get Relationships from those permissions
        // we apply Relationship Scopes, so Collection got models from those scopes

        $globalScopeName = "{$this->model()}\\{$action}";
        $model = $this->model();
        $keyName = (new $model())->getKeyName();
        $signature = $this->getSignature($action);

        // As authorize() called without model, it checks only roles
        // If it returns true — user may observe all recordSet

        if (!$this->authorize($action, $user)) {

            // User not authorized by his concrete roles
            // But may be has access through relationships

            // Get all user relationship scopes and combine them into one query
            // Then scope model with that query
            // If model has no relationship scopes, will return empty scope

            if ($user && ($relationships = $this->getModelRolesForSignature($signature))) {
                $model::addGlobalScope($globalScopeName, function (Builder $query) use ($relationships, $user) {
                    foreach ($relationships as $relationship) {
                        $scopeName = $this->getScopeName($relationship);
                        $query->orWhere(function (Builder $query) use ($scopeName, $user) {
                            $query->$scopeName($user);
                        });
                    }
                });
            } else {
                // User is anon or developer not defined any relationship scopes
                // Apply empty scope to prevent user access to unauthorized models
                $model::addGlobalScope($globalScopeName, function (Builder $query) use ($keyName) {
                    $query->where($keyName, 0);
                });
            }

        //} else {
            // User has total access to whole recordSet
        }
    }

    /**
     * Returns QueryBuilder associated to relationship
     * @param string $relationship
     * @param User $user
     * @return Builder|null
     */
    private function relationshipQuery($relationship, ?User $user)
    {
        if ($scopeName = $this->getScopeName($relationship)) {
            return $this->model()::$scopeName($user);
        } else {
            return null;
        }
    }

    /**
     * Returns concrete user roles
     * @param User|Roles|null $user
     * @return array
     */
    protected function getUserNonModelRoles(?User $user)
    {
        if ($user) {
            if (!isset($this->cache["user-roles"])) {
                $this->cache["user-roles"] =
                    $this->applyNamespace(self::RoleNamespace, $user->roles->pluck('slug')->toArray());
            }
            return $this->cache["user-roles"];
        } else {
            return [];
        }
    }

    /**
     * Returns set of relationships between current Model and current User
     * @param User|null $user
     * @param $model
     * @return array
     */
    protected function getUserModelRoles(?User $user = null, ?Model $model = null)
    {
        if ($user) {
            $roles = $this->applyNamespace(self::BuiltInNamespace, ['any']);

            if ($model) {
                foreach ($this->relationships as $relationship) {
                    if ($query = $this->relationshipQuery($relationship, $user)) {
                        // Check if given Model relates to User through $relationship
                        $query->where($model->getKeyName(), $model->getKey());
                        if ($query->count()) {
                            $roles[] = $this->applyNamespace($this->getPolicyNamespace(), $relationship);
                        }
                    }
                }
            }

        } else {
            $roles = $this->applyNamespace(self::BuiltInNamespace, ['guest']);
        }

        return $roles;
    }

    /**
     * Signature is a Model+Action string, used as 'action' in abstract sense
     * @param string $action
     * @return string
     */
    protected function getSignature($action)
    {
        return $this->getPolicyNamespace() . ':' . $action;
    }

    /**
     * Returns model roles without namespace(!), allowed to perform given action
     * @param string $signature
     * @return array
     */
    protected function getModelRolesForSignature($signature)
    {
        $relationships = $this->getPermissions($signature);

        // Clean out namespaces
        $relationships = array_map(function ($n) {
            $n = explode('\\', $n);
            $n = array_pop($n);
            return Str::snake($n);
            // TODO check string transformations
        }, $relationships);

        // Keep only relationships relevant to the Policy
        $relationships = array_intersect($relationships, $this->relationships);

        return $relationships;
    }

    /**
     * Checks User ability to perform Action against Model
     * @param string $action
     * @param User|null $user
     * @param Model|null $model
     * @return bool
     */
    protected function authorize($action, ?User $user, Model $model = null)
    {
        $roles = $this->getUserRoles($user, $model);
        $defaults = (array)$this->getDefaults($action);

        return
            in_array('*', $defaults)
            ||
            array_intersect($roles, $defaults)
            ||
            array_intersect($roles, $this->getPermissions($this->getSignature($action)));
    }

    /**
     * Roles and Relationships, that User plays in given Model
     * @param User|null $user
     * @param Model|null $context
     * @return array
     */
    protected function getUserRoles(?User $user, Model $context = null)
    {
        $roles = array_merge(
            $this->getUserModelRoles($user, $context),
            $this->getUserNonModelRoles($user)
        );
        return $roles;
    }

    /**
     * Get roles for signature
     * @param string $signature
     * @return array
     */
    protected function getPermissions($signature)
    {
        // Take permissions with signature and user role
        $permissions = Permission::cached()->filter(
            function (Permission $perm) use ($signature) {
                return ($perm->signature == $signature);
            }
        );
        return $permissions->pluck('role')->toArray();
    }

    /**
     * Get actions allowed to the User in given Model
     * @param User|null $user
     * @param Model|null $context
     * @return array|string[]
     */
    public function getAbilities(?User $user, Model $context = null)
    {
        $abilities = [];

        try {
            $helper = new ReflectionHelper();
            if ($context) {
                $actions = $helper->getModelActions(get_class($this));
            } else {
                $actions = $helper->getNonModelActions(get_class($this));
            }
        } catch (\ReflectionException $e) {
            $actions = [];
        }

        foreach ($actions as $action) {
            if ($this->authorize($action, $user, $context)) {
                $abilities[] = $action;
            }
        }

        return $abilities;
    }
}
