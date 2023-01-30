<?php

namespace App\Admin\Middleware;

use Closure;

class UserGroup
{
    public function handle($request, Closure $next)
    {
        \Blocs\Menu::checkGroup() || abort(403);

        return $next($request);
    }
}
