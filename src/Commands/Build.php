<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;

class Build extends Command
{
    use StaticTrait;

    protected $signature;
    protected $description;

    private static $publicPath;
    private static $staticPath;
    private static $proxy;

    private static $buildList = [];
    private static $uploadList = [];
    private static $deleteList = [];

    public function __construct($signature, $description)
    {
        $this->signature = $signature;
        $this->description = $description;

        self::$publicPath = public_path();
        self::$staticPath = base_path('static');
        self::$proxy = new Proxy();

        parent::__construct();
    }

    public function handle()
    {
        $path = self::getPath($this->argument('path'));

        // publicをstaticにコピー
        $this->copyDir(self::$publicPath.$path, self::$staticPath.$path);

        // staticの静的コンテンツを更新
        $this->updateStaticDir(self::$staticPath.$path);

        // middlewareからのbuild対象を取得
        $this->getBuildList();

        // build
        while (self::$buildList) {
            $requestUri = array_shift(self::$buildList);
            $this->buildStatic($requestUri);
        }

        // 更新のあったファイルを差分反映
        empty(env('AWS_BUCKET')) || $this->updateS3Disk();
    }

    // pathの補正
    private static function getPath($path)
    {
        if (empty($path)) {
            return '';
        }

        if ('/' !== substr($path, 0, 1)) {
            $path = '/'.$path;
        }
        if ('/' === substr($path, -1)) {
            $path = substr($path, 0, -1);
        }

        return $path;
    }

    private function copyDir($publicDir, $staticDir)
    {
        if (!is_dir($publicDir)) {
            return;
        }

        is_dir($staticDir) || mkdir($staticDir, 0777, true) && chmod($staticDir, 0777);

        $fileList = scandir($publicDir);
        foreach ($fileList as $file) {
            if ('.' == substr($file, 0, 1) && '.gitkeep' != $file) {
                continue;
            }

            if (is_dir($publicDir.'/'.$file)) {
                $this->copyDir($publicDir.'/'.$file, $staticDir.'/'.$file);
                continue;
            }

            $fileExt = pathinfo($publicDir.'/'.$file, PATHINFO_EXTENSION);
            if ('php' == $fileExt) {
                // phpファイルはコピーしない
                continue;
            }

            if (file_exists($staticDir.'/'.$file)) {
                $beforeContents = file_get_contents($staticDir.'/'.$file);
                $afterContents = file_get_contents($publicDir.'/'.$file);

                if ($beforeContents === $afterContents) {
                    continue;
                }
            }

            $staticName = str_replace(self::$staticPath, '', $staticDir.'/'.$file);
            array_push(self::$uploadList, $staticName);

            // コンテンツに更新があった
            copy($publicDir.'/'.$file, $staticDir.'/'.$file) && chmod($staticDir.'/'.$file, 0666);

            echo "Update \"{$staticName}\"\n";
        }
    }

    private function updateStaticDir($staticDir)
    {
        $fileList = scandir($staticDir);
        foreach ($fileList as $file) {
            if ('.' == substr($file, 0, 1)) {
                continue;
            }

            $staticLoc = $staticDir.'/'.$file;

            if (is_dir($staticLoc)) {
                self::updateStaticDir($staticLoc);
                continue;
            }

            $publicFile = str_replace(self::$staticPath, self::$publicPath, $staticLoc);
            if (file_exists($publicFile)) {
                // 動的コンテンツではない
                continue;
            }

            $requestUri = $this->getRequestUri($staticLoc);
            if (
                // 対象外のurl
                false === $this->checkStaticUrl($requestUri) ||
                // 静的コンテンツが作れなかった
                false === ($newStaticLoc = $this->buildStatic($requestUri)) ||
                // 静的コンテンツの名前が変更
                $newStaticLoc !== $staticLoc
            ) {
                $staticName = substr($staticLoc, strlen(self::$staticPath));
                array_push(self::$deleteList, $staticName);

                // 不要な静的コンテンツを削除
                unlink($staticLoc);

                echo "Delete \"{$staticName}\"\n";
            }
        }

        // ファイルがないディレクトリは削除
        glob($staticDir.'/*') || rmdir($staticDir);
    }

    private function getBuildList()
    {
        $buildListPath = BLOCS_CACHE_DIR.'/buildList.txt';
        if (!file_exists($buildListPath)) {
            return [];
        }

        // リストを読み込んだら削除
        $buildList = file_get_contents($buildListPath);
        unlink($buildListPath);

        $buildList = empty($buildList) ? [] : explode("\n", $buildList);
        $buildList = array_filter($buildList, 'strlen');

        self::$buildList = array_merge(self::$buildList, $buildList);
        self::$buildList = array_merge(array_unique(self::$buildList));
    }

    private function updateS3Disk()
    {
        $s3Disk = \Storage::disk('s3');

        foreach (self::$uploadList as $uploadFile) {
            $s3Disk->putFileAs(dirname($uploadFile), self::$staticPath.$uploadFile, basename($uploadFile));

            echo "S3 Upload \"{$uploadFile}\"\n";
        }

        foreach (self::$deleteList as $deleteFile) {
            $s3Disk->delete($deleteFile);

            echo "S3 Delete \"{$deleteFile}\"\n";
        }
    }
}
