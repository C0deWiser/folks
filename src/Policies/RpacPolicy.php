<?php

namespace Codewiser\Rpac\Policies;

use Codewiser\Rpac\Helpers\RpacHelper;
use Codewiser\Rpac\Traits\RPAC;
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

    /**
     * Per-hit data storage
     * @var array
     */
    private $cache = [];


    /**
     * The Policy pseudo-name for use in permission table
     * @return string
     */
    public function getNamespace()
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
     * Default (built-in) permissions
     * @param string $action
     * @return array|string|null|void return namespaced(!) roles, allowed to $action
     */
    public function getDefaults($action)
    {
    }

    /**
     * Get list of model actions
     * @return array|string[]
     * @example [view, update, delete, ...]
     */
    public function getModelActions()
    {
        try {
            return $this->getActions('model');
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    /**
     * Get list of non-model actions
     * @return array|string[]
     * @example [viewAny, create]
     */
    public function getNonModelActions()
    {
        try {
            return $this->getActions('non-model');
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    /**
     * Get list of available Policy actions
     * @param string $option {model => returns actions for model; non-model => returns actions for non-model}
     * @return array
     * @throws \ReflectionException
     */
    private function getActions($option = null)
    {
        $reflection = new \ReflectionClass($this);
        return array_values(
            array_map(function (\ReflectionMethod $n) {
                return $n->name;
            }, array_filter(
                    $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                    function (\ReflectionMethod $n) use ($option) {
                        if ($n->isPublic()) {

                            /* @var \ReflectionParameter $userParam */
                            /* @var \ReflectionParameter $modelParam */
                            $userParam = @$n->getParameters()[0];
                            $modelParam = @$n->getParameters()[1];

                            if ($option != 'non-model') {
                                // Require both parameters
                                if ($userParam && $modelParam && $userParam->name == 'user' && $modelParam->name == 'model') {
                                    return $n;
                                }
                            }

                            if ($option != 'model') {
                                // Require only user parameter
                                if ($userParam && !$modelParam && $userParam->name == 'user') {
                                    return $n;
                                }
                            }
                        }
                        return null;
                    })
            )
        );
    }

    public function viewAny(?User $user)
    {
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
     * Get user roles
     * @param User|Roles|null $user
     * @return array
     */
    protected function getUserNonModelRoles(?User $user)
    {
        if ($user) {

            if (!isset($this->cache["user-roles"])) {
                $this->cache["user-roles"] = $user->getRoles();
            }

            $roles = $this->cache["user-roles"];
        } else {
            $roles = ['guest'];
        }
        return $roles;
    }

    /**
     * Get relationships between given Model and given User
     * @param User|null $user
     * @param Model|RPAC $model
     * @return array
     */
    protected function getUserModelRoles(User $user, Model $model)
    {
        /** @var Model $user */
        /** @var Builder $query */

        $roles = [];

        foreach ($model::getRelationshipListing() as $qualifiedRelationship) {
            // Check if given Model relates to User through relation

            $relationship = Str::afterLast($qualifiedRelationship, '\\'); // clear relationship from namespace
            $singleRelation = Str::camel($relationship); // author() or chiefOfficer()
            $pluralRelation = Str::pluralStudly($singleRelation); // authors() or chiefOfficers()

            if (method_exists($model, $singleRelation)) {
                // $model->author() relation found
                $suspect = $model->$singleRelation;
            } elseif (method_exists($model, $pluralRelation)) {
                // $model->managers() relation found
                $suspect = $model->$pluralRelation()->whereKey($user->getKey())->get();
            } else {
                $suspect = null;
            }

            if ($user->is($suspect)) {
                $roles[] = $qualifiedRelationship;
            }
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
        return $this->getNamespace() . ':' . $action;
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
        $permissions = $this->getPermissions($action);

        return
            in_array('*', $permissions)
            ||
            array_intersect($this->getUserRoles($user, $model), $permissions);
    }

    /**
     * Roles and Relationships, that User plays in given Model
     * @param User|null $user
     * @param Model|null $model
     * @return array
     */
    protected function getUserRoles(?User $user, Model $model = null)
    {
        $roles = array_merge(
            ($user && $model) ? $this->getUserModelRoles($user, $model) : [],
            $this->getUserNonModelRoles($user)
        );
        return $roles;
    }

    /**
     * Get roles allowed to perform action
     * @param string $action
     * @return array
     */
    public function getPermissions($action)
    {
        $signature = $this->getSignature($action);

        // Take permissions with signature and user role
        $permissions = Permission::cached()->filter(
            function (Permission $perm) use ($signature) {
                return ($perm->signature == $signature);
            }
        );
        return array_merge(
            (array)$this->getDefaults($action),
            (array)$permissions->pluck('role')->toArray()
        );
    }


}
