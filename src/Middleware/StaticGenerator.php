<?php

namespace Blocs\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaticGenerator
{
    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        if (200 !== $response->getStatusCode()) {
            return $response;
        }

        $staticFile = public_path($request->server->get('REQUEST_URI').'/index.html');

        // 保存するディレクトリを準備
        $staticDir = dirname($staticFile);
        is_dir($staticDir) || mkdir($staticDir, 0777, true) && chmod($staticDir, 0777);

        // キャッシュ作成
        file_put_contents($staticFile, $response->getContent());

        return $response;
    }
}
