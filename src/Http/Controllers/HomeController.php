<?php

namespace Codewiser\Folks\Http\Controllers;

use Codewiser\Folks\Folks;
use Illuminate\Support\Facades\App;

class HomeController extends Controller
{
    /**
     * Single page application catch-all route.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('rpac::layout', [
            'assetsAreCurrent' => Folks::assetsAreCurrent(),
            'cssFile' => 'app.css',
            'folksScriptVariables' => Folks::scriptVariables(),
            'isDownForMaintenance' => App::isDownForMaintenance(),
        ]);
    }
}
