<?php

namespace Blocs\Middleware;

use Blocs\Menu;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Role
{
    public function handle(Request $request, Closure $next): Response
    {
        // 現在のルートに必要な役割を持たないユーザーを拒否する
        if (! Menu::checkRole()) {
            abort(403);
        }

        return $next($request);
    }
}
