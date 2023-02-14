<?php

namespace Blocs\Middleware;

use Closure;

class StaticGenerator
{
    protected $staticPath;
    protected $buildConfigPath;
    protected $buildConfig;

    private $staticExtension;

    public function __construct()
    {
        $this->staticPath = base_path().'/static';
        $this->buildConfigPath = $this->staticPath.'/_build.json';
    }

    public function handle($request, Closure $next, $staticExtension = null)
    {
        $response = $next($request);

        $this->staticExtension = $staticExtension;
        $staticName = $this->getStaticName($_SERVER['REQUEST_URI']);
        $staticLoc = $this->staticPath.$staticName;

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

        // オリジナルのURLを保存
        $this->updateBuildConfig($staticName, url($_SERVER['REQUEST_URI']), !file_exists($staticLoc));

        $content = $this->convertStaticContent($response->content());
        file_put_contents($staticLoc, $content) && chmod($staticLoc, 0666);

        return $response;
    }

    // 静的ファイル名を生成
    private function getStaticName($staticName)
    {
        $action = basename($staticName);
        if (false !== strpos($action, '.')) {
            // 拡張子あり
            return $staticName;
        }

        if ('download' === $action) {
            $staticName = dirname($staticName);
            $fileName = basename($staticName);
            if (false !== strpos($fileName, '.')) {
                // サイズ指定なし
                return $staticName;
            }
            $fileSize = $fileName;

            // サイズ指定あり
            $staticName = dirname($staticName);
            $fileName = basename($staticName);
            $staticName = dirname($staticName);

            return "{$staticName}/{$fileSize}/{$fileName}";
        }

        $staticName = str_replace('?', '_', $staticName);
        if (isset($this->staticExtension)) {
            empty($this->staticExtension) || $staticName .= '.'.$this->staticExtension;
        } else {
            $staticName .= '.html';
        }

        return $staticName;
    }

    private function replaceStaticName($htmlBuff)
    {
        $originalUrlList = [];
        $staticNameList = [];
        foreach ($this->buildConfig as $staticName => $originalUrl) {
            if (!is_string($originalUrl)) {
                continue;
            }

            $originalUrlList[] = $originalUrl;
            $staticNameList[$originalUrl] = $staticName;
        }

        // 長いURLから置換
        array_multisort(array_map('strlen', $originalUrlList), SORT_DESC, $originalUrlList);

        foreach ($originalUrlList as $originalUrl) {
            foreach (['"', "'"] as $quotes) {
                // 静的コンテンツへのパスに置換
                $htmlBuff = str_replace(
                    $quotes.$originalUrl.$quotes,
                    $quotes.$staticNameList[$originalUrl].$quotes,
                    $htmlBuff
                );
            }
        }

        return $htmlBuff;
    }

    // HTMLのリンクを静的ファイル名に置換
    private function convertStaticContent($content)
    {
        $htmlList = \Blocs\Compiler\Parser::parse($content);
        $this->buildConfig = $this->readBuildConfig();

        $convertedContent = '';
        while ($htmlList) {
            $htmlBuff = array_shift($htmlList);

            if (!is_array($htmlBuff)) {
                $convertedContent .= $this->replaceStaticName($htmlBuff);
                continue;
            }

            $tag = $htmlBuff['tag'];
            $attrList = $htmlBuff['attribute'];
            if ('input' === $tag && isset($attrList['type']) && 'hidden' === $attrList['type']) {
                // tokenなどは静的コンテンツに入れない
                continue;
            }

            $convertedContent .= $this->replaceStaticName($htmlBuff['raw']);
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

        file_put_contents($buildConfigPath, json_encode($buildConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n") && chmod($buildConfigPath, 0666);
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

        file_put_contents($buildConfigPath, json_encode($buildConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n") && chmod($buildConfigPath, 0666);
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

        file_put_contents($buildConfigPath, json_encode($buildConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n") && chmod($buildConfigPath, 0666);
    }
}
