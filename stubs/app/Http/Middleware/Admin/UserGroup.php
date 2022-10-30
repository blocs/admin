<?php

namespace App\Http\Middleware\Admin;

use Closure;

class UserGroup
{
    public function handle($request, Closure $next)
    {
        \Blocs\Navigation::check_group() || abort(403);

        return $next($request);
    }
}
