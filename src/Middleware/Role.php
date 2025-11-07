<?php

namespace Blocs\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Role
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->hasRequiredRole()) {
            abort(403);
        }

        return $next($request);
    }

    private function hasRequiredRole(): bool
    {
        return \Blocs\Menu::checkRole();
    }
}
