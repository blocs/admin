<?php

namespace Blocs\Commands;

use Blocs\Middleware\StaticGenerator;

class Proxy
{
    use \Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

    private static $cache;

    public function __construct()
    {
        $this->app = app();

        // buildでは無効にする
        $this->withoutMiddleware([StaticGenerator::class]);
    }

    public function cache($url)
    {
        if (isset(self::$cache[$url])) {
            // キャッシュ
            return self::$cache[$url];
        }

        $response = $this->get($url);

        $cache = new \stdClass();
        $cache->status = $response->status();

        if (200 != $cache->status) {
            self::$cache[$url] = $cache;

            return $cache;
        }

        $content = $response->content();
        $extension = \Blocs\Thumbnail::extension($content);
        $cache->extension = $extension;

        // 容量が増えないようにhtmlのみキャッシュ
        'html' === $extension && $cache->content = $content;

        self::$cache[$url] = $cache;

        return $cache;
    }
}
