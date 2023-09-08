<?php

namespace Blocs\Commands;

use Blocs\Middleware\StaticGenerator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Proxy
{
    use \Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

    private static $cache;
    protected $app;

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
        $cache->status = $response->getStatusCode();

        if (200 != $cache->status) {
            self::$cache[$url] = $cache;

            return $cache;
        }

        if ($response->baseResponse instanceof StreamedResponse) {
            $content = $response->streamedContent();
        } else {
            $content = $response->getContent();
        }
        $extension = \Blocs\Thumbnail::extension($content);

        $cache->extension = $extension;
        $cache->content = $content;

        // 容量が増えないようにhtmlのみキャッシュ
        'html' === $extension && self::$cache[$url] = $cache;

        return $cache;
    }
}
