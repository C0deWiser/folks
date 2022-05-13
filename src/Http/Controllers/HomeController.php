<?php

namespace Codewiser\Folks\Http\Controllers;

use Codewiser\Folks\Contracts\AssetProviderContract;
use Codewiser\Folks\Folks;
use Illuminate\Support\Facades\App;

class HomeController extends Controller
{
    /**
     * Single page application catch-all route.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(AssetProviderContract $assets)
    {
        return view('folks::layout', [
            'assetsAreCurrent' => $assets->assetsAreCurrent(),
            'cssFile' => 'app.css',
            'folksScriptVariables' => $assets->scriptVariables(),
            'isDownForMaintenance' => App::isDownForMaintenance(),
        ]);
    }
}
