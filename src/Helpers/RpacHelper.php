<?php

namespace Codewiser\Rpac\Helpers;

use Codewiser\Rpac\Traits\RPAC;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Codewiser\Rpac\Policies\RpacPolicy;
use Codewiser\Rpac\Role;

class RpacHelper
{
    /**
     * @var RpacPolicy
     */
    protected $policy;

    /**
     * @var string
     */
    protected $model;

    public function __construct(string $model)
    {
        $this->model = $model;
        $this->policy = $model::getPolicy();
    }

    /**
     * Prepend namespace to string or array of strings
     * @param $namespace
     * @param $data
     * @return array|string
     */
    public static function applyNamespace($namespace, $data)
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
     * Get full list of Models with RpacPolicy
     * @return array|string[]
     */
    public static function getRpacModels()
    {
        $models = [];

        foreach (self::scanDir(app_path()) as $file) {
            $className = Str::replaceFirst(app_path() . '/', '', $file);
            $className = str_replace('/', '\\', $className);
            $className = app()->getNamespace() . substr($className,0,-4);
            if (($policy = Gate::getPolicyFor($className)) && $policy instanceof RpacPolicy) {
                $models[] = $className;
            }
        }

        return $models;
    }

    protected static function scanDir($path)
    {
        $files = [];

        foreach ((array)scandir($path) as $file) {
            if ($file === '.' or $file === '..') continue;
            $filename = $path . '/' . $file;
            if (is_dir($filename)) {
                $files = array_merge($files, self::scanDir($filename));
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
    public static function getNonModelRoles()
    {
        return array_merge(
            Role::all()->pluck('slug')->toArray(),
            ['guest', 'any']
        );
    }

    /**
     * Get list of model actions of given policy
     * @return array|string[]
     * @throws \ReflectionException
     * @example [view, update, delete, ...]
     */
    public function getModelActions()
    {
        return $this->getActions('model');
    }

    /**
     * Get list of non-model actions of given policy
     * @return array|string[]
     * @throws \ReflectionException
     * @example [viewAny, create]
     */
    public function getNonModelActions()
    {
        return $this->getActions('non-model');
    }

    /**
     * Return namespace, used by the policy.
     * Keep in mind, that different policies may share one namespace.
     * In that case those policies are identical from RPAC point of view.
     * @return string
     * @example App\Settings
     */
    public function getNamespace()
    {
        return $this->policy->getNamespace();
    }

    /**
     * Return all relationships between user and model, declared by given policy. Applicable only to model events
     * @return array|string[]
     * @example [App\Post\Author, App\Post\Manager]
     */
    public function getModelRoles()
    {
        $model = $this->model;
        return self::applyNamespace($this->getNamespace(), (new $model())->relationships);
    }

    /**
     * Return default rule. If set, you can not override it from user interface
     * @param string $action
     * @param string $role
     * @return bool|null
     */
    public function getBuiltInPermission($action, $role)
    {
        $defaults = $this->policy->getDefaults($action);
        return $defaults == '*' || in_array($role, (array)$defaults);
    }

    /**
     * Returns list of available Policy actions
     * @param string $option {model => returns actions for model; non-model => returns actions for non-model}
     * @return array
     * @throws \ReflectionException
     */
    protected function getActions($option = null)
    {
        $reflection = new \ReflectionClass($this->policy);
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