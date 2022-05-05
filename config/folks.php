<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Folks Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Folks will be accessible from. If this
    | setting is null, Folks will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('FOLKS_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Folks Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Folks will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('FOLKS_PATH', 'folks'),

    /*
    |--------------------------------------------------------------------------
    | Folks Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Folks route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'web'
    ],

];
