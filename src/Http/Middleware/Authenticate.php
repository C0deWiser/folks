<?php

namespace Codewiser\Folks\Http\Middleware;

use Codewiser\Folks\Folks;

class Authenticate
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|null
     */
    public function handle($request, $next)
    {
        return Folks::check($request) ? $next($request) : abort(403);
    }
}
