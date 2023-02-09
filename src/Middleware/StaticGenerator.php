<?php

namespace Blocs\Middleware;

use Closure;

class StaticGenerator
{
    protected $staticPath;
    protected $buildConfigPath;

    public function __construct()
    {
        $this->staticPath = base_path().'/static';
        $this->buildConfigPath = $this->staticPath.'/_build.json';
    }

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $staticPath = $this->staticPath;
        if (!$staticPath) {
            return $response;
        }

        $staticName = self::getStaticName($_SERVER['REQUEST_URI'], false);
        $staticLoc = $staticPath.$staticName;

        if (200 != $response->status()) {
            // 対象ファイルを削除
            if (file_exists($staticLoc)) {
                unlink($staticLoc);
                $this->deleteBuildConfig($staticName);
            }

            return $response;
        }

        // 保存するディレクトリを準備
        $staticDir = dirname($staticLoc);
        is_dir($staticDir) || mkdir($staticDir, 0777, true) && chmod($staticDir, 0777);

        // 静的コンテンツを追加したか
        $isUpload = !file_exists($staticLoc);

        $content = self::convertStaticContent($response->content());
        file_put_contents($staticLoc, $content);

        // オリジナルのURLを保存
        $this->updateBuildConfig($staticName, url($_SERVER['REQUEST_URI']), $isUpload);

        return $response;
    }

    // 静的ファイル名を生成
    private static function getStaticName($staticLoc, $linkFlag = true)
    {
        $uriForPath = request()->getUriForPath('');
        if ($linkFlag && strncmp($staticLoc, $uriForPath, strlen($uriForPath))) {
            return $staticLoc;
        }

        $staticName = str_replace($uriForPath, '', $staticLoc);
        $action = basename($staticName);

        if ('download' === $action) {
            $staticName = dirname($staticName);
            $action = basename($staticName);
            if (false !== strpos($action, '.')) {
                return $staticName;
            }

            // サイズ指定あり
            $staticName = dirname($staticName);
            $fileName = basename($staticName);
            $staticName = dirname($staticName);

            return "{$staticName}/{$action}/{$fileName}";
        }

        if (false === strpos($action, '.')) {
            $staticName = str_replace('?', '_', $staticName);
            $staticName .= '.html';
        }

        return $staticName;
    }

    // HTMLのリンクを静的ファイル名に置換
    private static function convertStaticContent($content)
    {
        $htmlList = \Blocs\Compiler\Parser::parse($content);

        $convertedContent = '';
        while ($htmlList) {
            $htmlBuff = array_shift($htmlList);

            if (!is_array($htmlBuff)) {
                $convertedContent .= $htmlBuff;
                continue;
            }

            $tag = $htmlBuff['tag'];
            $attrList = $htmlBuff['attribute'];
            if ('input' === $tag && isset($attrList['type']) && 'hidden' === $attrList['type']) {
                // tokenなどは静的コンテンツに入れない
                continue;
            }

            foreach (['href', 'src'] as $arrtibute) {
                if (!isset($attrList[$arrtibute])) {
                    continue;
                }

                // 相対パスに変換
                $staticName = self::getStaticName($attrList[$arrtibute]);
                $htmlBuff['raw'] = str_replace($attrList[$arrtibute], $staticName, $htmlBuff['raw']);
            }

            $convertedContent .= $htmlBuff['raw'];
        }

        return $convertedContent;
    }

    public function readBuildConfig()
    {
        $buildConfigPath = $this->buildConfigPath;

        return is_file($buildConfigPath) ? json_decode(file_get_contents($buildConfigPath), true) : [];
    }

    public function updateBuildConfig($staticName, $url, $isUpload)
    {
        $buildConfigPath = $this->buildConfigPath;

        $buildConfig = $this->readBuildConfig();
        $buildConfig[$staticName] = $url;

        if ($isUpload) {
            // 新規追加ファイルはアップロードする
            empty($buildConfig['_upload']) && $buildConfig['_upload'] = [];
            $buildConfig['_upload'][] = $staticName;
            $buildConfig['_upload'] = array_unique($buildConfig['_upload']);
        }

        file_put_contents($buildConfigPath, json_encode($buildConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) && chmod($buildConfigPath, 0666);
    }

    public function deleteBuildConfig($staticName)
    {
        $buildConfigPath = $this->buildConfigPath;

        $buildConfig = $this->readBuildConfig();
        unset($buildConfig[$staticName]);

        // ファイルを削除する
        empty($buildConfig['_delete']) && $buildConfig['_delete'] = [];
        $buildConfig['_delete'][] = $staticName;
        $buildConfig['_delete'] = array_unique($buildConfig['_delete']);

        file_put_contents($buildConfigPath, json_encode($buildConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) && chmod($buildConfigPath, 0666);
    }

    public function refreshBuildConfig($uploadList = [], $deleteList = [])
    {
        $buildConfigPath = $this->buildConfigPath;
        if (!is_file($buildConfigPath)) {
            return;
        }

        $buildConfig = $this->readBuildConfig();
        foreach ($buildConfig as $staticName => $requestUri) {
            if (is_file($this->staticPath.'/'.$staticName)) {
                continue;
            }

            // 静的コンテンツがない
            unset($buildConfig[$staticName]);
        }

        empty($uploadList) || $buildConfig['_upload'] = $uploadList;
        empty($deleteList) || $buildConfig['_delete'] = $deleteList;

        if (empty($buildConfig)) {
            unlink($buildConfigPath);

            return;
        }

        file_put_contents($buildConfigPath, json_encode($buildConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) && chmod($buildConfigPath, 0666);
    }
}
