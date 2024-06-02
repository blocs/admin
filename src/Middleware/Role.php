<?php

namespace Blocs\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Role
{
    public function handle(Request $request, \Closure $next): Response
    {
        \Blocs\Menu::checkRole() || abort(403);

        return $next($request);
    }
}
