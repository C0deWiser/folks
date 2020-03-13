<?php

namespace Codewiser\Rpac\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccessController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:su');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->has('model')) {
            $models = self::getModelsWithPolicies(null, !$request->has('all'), $request->has('abilities'));

            if($request->has('softdeletes')) {
                $models = $models->map(function($model) {
                    $uses = class_uses($model['model']);
                    $model['softdeletes'] = isset($uses['Illuminate\\Database\\Eloquent\\SoftDeletes']);
                    return $model;
                });
            }

            if($request->model) {
                return $models->where('model', $request->model)->values();
            }


            return $models;
        }

        abort(403);
    }

    public static function getModelsWithPolicies($modelPaths = null, $onlyHasPolicy = true, $withAbilities = false)
    {
        $models = self::getModels($modelPaths);

        $models = $models->map(function($model) use ($withAbilities) {
            $policy = Gate::getPolicyFor($model['model']);

            $model['policy'] = $policy ? get_class($policy) : null;

            if($policy && $withAbilities) {
                $abilityMap = ((new self)->resourceAbilityMap());
                $policyMethods = array_diff(get_class_methods($model['policy']), ["__construct","__call","authorize","authorizeForUser","authorizeResource"]);

                $model['actions'] = array_values(array_unique(array_merge($policyMethods, array_values($abilityMap))));
                //$model['map'] = $abilityMap;
                $model['map'] = array_merge($abilityMap, array_combine($policyMethods, $policyMethods));
            }

            return $model;
        });

        if($onlyHasPolicy) {
            $models = $models->filter(function($model){
                return $model['policy'];
            })->values();
        }

        return $models;
    }


    public static function getModels($modelPaths = null)
    {
        if(!$modelPaths) $modelPaths = app_path();
        if(is_string($modelPaths)) $modelPaths = [$modelPaths];
        //if(!is_array($modelPaths)) \Exception::

        $models = collect();

        foreach($modelPaths as $path) {
            $appFiles = scandir($path);
            foreach ($appFiles as $file) {
                if ($file === '.' or $file === '..') continue;
                $filename = $path . '/' . $file;
                if (!is_dir($filename)) {
                    $models->push(['model' => ucfirst(basename($path)) . '\\' . substr($file,0,-4)]);
                }
            }
        }

        return $models;
    }
}
