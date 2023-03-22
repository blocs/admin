<?php

namespace Blocs\Middleware;

class StaticGenerator
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);

        if (200 !== $response->status()) {
            return $response;
        }

        $middlewareList = \Route::gatherRouteMiddleware($request->route());
        empty($middlewareList) && $middlewareList = [];
        foreach ($middlewareList as $middleware) {
            if (false !== strpos($middleware, '\Authenticate')) {
                // 認証があるページはbuildの対象外
                abort(403, 'Can not generate static page with authentication');
            }
        }

        // buildの対象に追加
        $this->addBuildList($request->server->get('REQUEST_URI'));

        return $response;
    }

    // 静的ファイル名を生成
    private function addBuildList($url)
    {
        // 保存するディレクトリを準備
        $staticDir = base_path('static');
        is_dir($staticDir) || mkdir($staticDir, 0777, true) && chmod($staticDir, 0777);

        $buildListPath = BLOCS_CACHE_DIR.'/buildList.txt';
        file_put_contents($buildListPath, $url."\n", FILE_APPEND) && chmod($buildListPath, 0666);
    }
}
