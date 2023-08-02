<?php

namespace Blocs\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheGenerator
{
    public function handle(Request $request, \Closure $next): Response
    {
        $cacheFile = storage_path('framework/cache/').md5($_SERVER['REQUEST_URI']);

        $response = $next($request);

        if (200 === $response->getStatusCode()) {
            file_put_contents($cacheFile, $response->getContent());
        }

        return $response;
    }
}
