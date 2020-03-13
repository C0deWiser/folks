<?php

namespace Codewiser\Rpac\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Codewiser\Rpac\Policies\RpacPolicy;
use Codewiser\Rpac\Role;

class ReflectionHelper
{

    const BuiltInNamespace = 'Core';
    const RoleNamespace = 'Role';

    /**
     * Prepend namespace to string or array of strings
     * @param $namespace
     * @param $data
     * @return array|string
     */
    private function applyNamespace($namespace, $data)
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
     * Get full list of policies
     * @return array|string[]
     * @example [App\Policies\PostPolicy, ...]
     */
    public function getPolicies()
    {
        $models = [];

        foreach ($this->scanDir(app_path()) as $file) {
            $className = Str::replaceFirst(app_path() . '/', '', $file);
            $className = str_replace('/', '\\', $className);
            $className = app()->getNamespace() . substr($className,0,-4);
            if ($policy = Gate::getPolicyFor($className)) {
                $models[] = get_class($policy);
            }
        }

        return $models;
    }

    protected function scanDir($path)
    {
        $files = [];

        foreach ((array)scandir($path) as $file) {
            if ($file === '.' or $file === '..') continue;
            $filename = $path . '/' . $file;
            if (is_dir($filename)) {
                $files = array_merge($files, $this->scanDir($filename));
            } else {
                $files[] = $filename;
            }
        }

        return $files;
    }

    /**
     * Return all system roles, applicable to any model and non-model
     * @return array|string[]
     * @example [Core\Guest, Role\Admin, ...]
     */
    public function getRoles()
    {
        return array_merge(
            $this->applyNamespace(self::RoleNamespace, Role::all()->pluck('slug')->toArray()),
            $this->applyNamespace(self::BuiltInNamespace, ['guest', 'any'])
        );
    }

    /**
     * Get list of model actions of given policy
     * @param string $policy
     * @return array|string[]
     * @throws \ReflectionException
     * @example [view, update, delete, ...]
     */
    public function getModelActions($policy)
    {
        return $this->getActions($policy, 'model');
    }

    /**
     * Get list of non-model actions of given policy
     * @param string $policy
     * @return array|string[]
     * @throws \ReflectionException
     * @example [viewAny, create]
     */
    public function getNonModelActions($policy)
    {
        return $this->getActions($policy, 'non-model');
    }

    /**
     * Return namespace, used by the policy.
     * Keep in mind, that different policies may share one namespace.
     * In that case those policies are identical from RPAC point of view.
     * @param string $policy
     * @return string
     * @example App\Settings
     */
    public function getNamespace($policy)
    {
        /** @var RpacPolicy $policy */
        $policy = new $policy();
        return $policy->getNamespace();
    }

    /**
     * Return all relationships between user and model, declared by given policy. Applicable only to model events
     * @param string $policy
     * @return array|string[]
     * @example [App\Post\Author, App\Post\Manager]
     */
    public function getRelationships($policy)
    {
        /** @var RpacPolicy $policy */
        $policy = new $policy();
        return $this->applyNamespace($this->getNamespace($policy), $policy->getRelationships());
    }

    /**
     * Return default rule. If set, you can not override it from user interface
     * @param string $policy
     * @param string $action
     * @param string $role
     * @return bool|null
     */
    public function getBuiltInPermission($policy, $action, $role)
    {
        /** @var RpacPolicy $policy */
        $policy = new $policy();
        return $policy->getPermission($action, $role);
    }

    /**
     * Returns list of available Policy actions
     * @param string $policy
     * @param string $option {model => returns actions for model; non-model => returns actions for non-model}
     * @return array
     * @throws \ReflectionException
     */
    protected function getActions(string $policy, $option = null)
    {
        $reflection = new \ReflectionClass($policy);
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
}