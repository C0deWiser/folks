<?php

namespace Codewiser\Rpac\Helpers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Codewiser\Rpac\Policies\RpacPolicy;

class RpacHelper
{
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


}