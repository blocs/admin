<?php

namespace Blocs\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheGenerator
{
    public function handle(Request $request, \Closure $next, $cacheSecond = 5): Response
    {
        $session_id = session()->getId();
        $sessionFile = storage_path('framework/sessions/').$session_id;
        $cacheFile = storage_path('framework/cache/').$_SERVER['REQUEST_METHOD'].md5($_SERVER['REQUEST_URI']);

        // flashの有無を確認
        if (file_exists($sessionFile)) {
            $flash = unserialize(file_get_contents($sessionFile))['_flash'];

            // flashがあるのでキャッシュは利用しない
            empty($flash['old']) || $cacheIgnore = true;
        }

        if (file_exists($cacheFile) && empty($cacheIgnore)) {
            if (time() < filemtime($cacheFile) || empty($cacheSecond)) {
                // キャッシュ出力
                echo file_get_contents($cacheFile);
                exit;
            }

            empty($cacheSecond) || touch($cacheFile, time() + $cacheSecond);
        }

        $response = $next($request);

        // キャッシュ作成
        if (200 === $response->getStatusCode() && empty($cacheIgnore)) {
            file_put_contents($cacheFile, $response->getContent());
            empty($cacheSecond) || touch($cacheFile, time() + $cacheSecond);
        }

        return $response;
    }
}
