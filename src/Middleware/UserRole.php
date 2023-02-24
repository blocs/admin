<?php

namespace Blocs\Middleware;

class UserRole
{
    public function handle($request, \Closure $next)
    {
        \Blocs\Menu::checkRole() || abort(403);

        return $next($request);
    }
}
