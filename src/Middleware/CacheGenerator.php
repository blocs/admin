<?php

namespace Blocs\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheGenerator
{
    public function handle(Request $request, \Closure $next): Response
    {
        $cacheFile = storage_path('framework/cache/').$_SERVER['REQUEST_METHOD'].md5($_SERVER['REQUEST_URI']);

        $response = $next($request);

        if (!defined('BLOCS_CACHE_IGNORE') && 200 === $response->getStatusCode()) {
            $_COOKIE['laravel_session_id'] = session()->getId();

            file_put_contents($cacheFile, $response->getContent());
            defined('BLOCS_CACHE_SECOND') && touch($cacheFile, time() + BLOCS_CACHE_SECOND);
        }

        return $response;
    }
}
