<?php

namespace Blocs\Commands;

trait StaticTrait
{
    /*
        $staticLoc 静的コンテンツのフルパス
        $staticName 静的コンテンツのstaticからのパス
    */

    private static $builtList = [];

    private function getStaticName($requestUri, $extension)
    {
        // 拡張子をつける
        $requestUri .= '.'.$extension;

        // 引数を置換
        $urlList = explode('?', $requestUri, 2);

        if (count($urlList) > 1) {
            $requestUri = $urlList[0].'---'.$urlList[1];
        }

        return $requestUri;
    }

    private function getRequestUri($staticLoc)
    {
        $requestUri = substr($staticLoc, strlen(self::$staticPath));

        // 拡張子をなくす
        $basename = basename($requestUri);
        $basename = explode('.', $basename, 2);
        count($basename) > 1 && $requestUri = substr($requestUri, 0, -1 * strlen($basename[1]) - 1);

        // 引数を戻す
        $urlList = explode('---', $requestUri, 2);

        if (count($urlList) > 1) {
            $requestUri = $urlList[0].'?'.$urlList[1];
        }

        return $requestUri;
    }

    private function buildStatic($requestUri)
    {
        if (isset(self::$builtList[$requestUri])) {
            // キャッシュ
            return self::$builtList[$requestUri];
        }

        // コンテンツを取得
        $response = self::$proxy->cache($requestUri);

        if (200 != $response->status) {
            self::$builtList[$requestUri] = false;

            return false;
        }

        $staticLoc = self::$staticPath.$this->getStaticName($requestUri, $response->extension);

        if ('html' === $response->extension) {
            // 静的リンクに置換
            $content = $this->convertStatic($response->content);
        } else {
            $content = $response->content;
        }

        $isUpload = false;
        if (file_exists($staticLoc)) {
            if (file_get_contents($staticLoc) !== $content) {
                $isUpload = true;
            }
        } else {
            $isUpload = true;
        }

        if (!$isUpload) {
            self::$builtList[$requestUri] = $staticLoc;

            return $staticLoc;
        }

        $staticName = substr($staticLoc, strlen(self::$staticPath));
        array_push(self::$uploadList, $staticName);

        // 保存するディレクトリを準備
        $staticDir = dirname($staticLoc);
        is_dir($staticDir) || mkdir($staticDir, 0777, true) && chmod($staticDir, 0777);

        file_put_contents($staticLoc, $content) && chmod($staticLoc, 0666);

        echo "Update \"{$staticName}\"\n";

        self::$builtList[$requestUri] = $staticLoc;

        return $staticLoc;
    }

    // 静的リンクに置換
    private function convertStatic($content)
    {
        $content = $this->parseContent($content);

        $buildList = $this->getStaticLink($content);

        return $this->replaceStaticLink($content, $buildList);
    }

    // htmlをパースして不要なタグなどを除去
    private function parseContent($content)
    {
        $htmlList = \Blocs\Compiler\Parser::parse($content);

        $parsedContent = '';
        while ($htmlList) {
            $htmlBuff = array_shift($htmlList);

            if (!is_array($htmlBuff)) {
                $parsedContent .= $htmlBuff;
                continue;
            }

            $tag = $htmlBuff['tag'];
            $attrList = $htmlBuff['attribute'];
            if ('input' === $tag && isset($attrList['type']) && 'hidden' === $attrList['type']) {
                // tokenなどは静的コンテンツに入れない
                continue;
            }

            $parsedContent .= $htmlBuff['raw'];
        }

        return $parsedContent;
    }

    // 静的リンクに置換するurlを取得
    private function getStaticLink($content)
    {
        preg_match_all('/["\'](https?:\/\/[^\s<"\']*)/', $content, $matchs);
        $urlList = array_unique($matchs[1]);
        if (empty($urlList)) {
            return [];
        }

        $baseUrl = url('');
        $buildList = [];
        foreach ($urlList as $url) {
            if (strncmp($url, $baseUrl, strlen($baseUrl))) {
                continue;
            }

            $this->checkStaticUrl($url) && $buildList[] = $url;
        }

        return $buildList;
    }

    // 静的リンクに置換するurlかを検証
    private function checkStaticUrl($url)
    {
        $route = Common::getRoute($url);
        if (empty($route)) {
            return false;
        }

        $isStatic = false;
        $middlewareList = \Route::gatherRouteMiddleware($route);
        empty($middlewareList) && $middlewareList = [];
        foreach ($middlewareList as $middleware) {
            if (false !== strpos($middleware, '\Authenticate')) {
                // 認証があるページ
                return false;
            }

            if (false !== strpos($middleware, '\StaticGenerator')) {
                $isStatic = true;
            }
        }

        return $isStatic;
    }

    // 静的リンクに置換
    private function replaceStaticLink($content, $urlList)
    {
        $baseUrl = url('');
        $staticLocList = [];
        foreach ($urlList as $url) {
            if ('/' === substr($url, -1)) {
                continue;
            }

            // コンテンツを取得
            $requestUri = substr($url, strlen($baseUrl));
            $response = self::$proxy->cache($requestUri);

            if (200 != $response->status) {
                continue;
            }

            // ビルドリストに追加
            array_push(self::$buildList, $requestUri);

            $staticLocList[$url] = $this->getStaticName($requestUri, $response->extension);
            $staticLocList[substr(json_encode($url), 1, -1)] = substr(json_encode($staticLocList[$url]), 1, -1);
        }

        // 長いURLから置換
        $urlList = array_keys($staticLocList);
        array_multisort(array_map('strlen', $urlList), SORT_DESC, $urlList);

        foreach ($urlList as $url) {
            foreach (['"', "'", '\\"', "\\'"] as $quotes) {
                // 静的コンテンツへのパスに置換
                $content = str_replace(
                    $quotes.$url.$quotes,
                    $quotes.$staticLocList[$url].$quotes,
                    $content
                );
            }
        }

        return $content;
    }
}
