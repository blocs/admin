<?php

namespace Blocs\Commands;

use Blocs\Middleware\StaticGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class Build extends Command
{
    protected $signature;
    protected $description;

    protected $publicPath;
    protected $staticPath;
    protected $staticGenerator;
    protected $buildConfig;
    protected $uploadList;
    protected $deleteList;

    public function __construct($signature, $description)
    {
        $this->signature = $signature;
        $this->description = $description;

        $this->publicPath = public_path();
        $this->staticPath = base_path().'/static';

        $this->staticGenerator = new StaticGenerator();
        $this->buildConfig = $this->staticGenerator->readBuildConfig();

        empty($this->buildConfig['_upload']) && $this->buildConfig['_upload'] = [];
        $this->uploadList = $this->buildConfig['_upload'];

        empty($this->buildConfig['_delete']) && $this->buildConfig['_delete'] = [];
        $this->deleteList = $this->buildConfig['_delete'];

        parent::__construct();
    }

    public function handle()
    {
        $path = self::getPath($this->argument('path'));

        // publicをstaticにコピー
        $this->copyDir($this->publicPath.$path, $this->staticPath.$path);

        // staticの静的コンテンツを更新
        $this->updateStaticDir($this->staticPath.$path);

        // 更新対象を取得
        list($uploadList, $deleteList, $noUploadList, $noDeleteList) = $this->getTargetFiles($path);

        // 更新のあったファイルを差分反映
        empty(env('AWS_BUCKET')) || $this->updateS3Disk($uploadList, $deleteList);

        // 設定ファイルを掃除
        $this->staticGenerator->refreshBuildConfig($noUploadList, $noDeleteList);
    }

    private function updateStaticDir($staticPath)
    {
        $fileList = scandir($staticPath);
        foreach ($fileList as $file) {
            if ('.' == substr($file, 0, 1)) {
                continue;
            }

            $staticFile = $staticPath.'/'.$file;
            if (is_dir($staticFile)) {
                self::updateStaticDir($staticFile);
                continue;
            }

            $publicFile = str_replace($this->staticPath, $this->publicPath, $staticFile);
            if (file_exists($publicFile)) {
                // 動的コンテンツではない
                continue;
            }

            if ('_build.json' === basename($file)) {
                // 設定ファイル
                continue;
            }

            $staticName = str_replace($this->staticPath, '', $staticFile);
            if (isset($this->buildConfig[$staticName])) {
                $beforeContents = file_get_contents($staticFile);

                // コンテンツのupdateをリクエスト
                $response = Http::get($this->buildConfig[$staticName]);

                if (!file_exists($staticFile)) {
                    // コンテンツを削除した
                    $this->deleteList[] = $staticName;

                    echo "Delete \"{$staticName}\""."\n";
                    continue;
                }

                $afterContents = file_get_contents($staticFile);
                if ($beforeContents !== $afterContents) {
                    // コンテンツに更新があった
                    $this->uploadList[] = $staticName;

                    echo "Update \"{$staticName}\""."\n";
                }
                continue;
            }

            echo "\e[7;31m"."Not found \"{$staticName}\""."\e[m"."\n";
        }
    }

    private function copyDir($originalDir, $targetDir)
    {
        if (!is_dir($originalDir)) {
            return;
        }

        is_dir($targetDir) || mkdir($targetDir, 0777, true) && chmod($targetDir, 0777);

        $fileList = scandir($originalDir);
        foreach ($fileList as $file) {
            if ('.' == substr($file, 0, 1) && '.gitkeep' != $file) {
                continue;
            }

            if (is_dir($originalDir.'/'.$file)) {
                $this->copyDir($originalDir.'/'.$file, $targetDir.'/'.$file);
                continue;
            }

            $fileExt = pathinfo($originalDir.'/'.$file, PATHINFO_EXTENSION);
            if ('php' == $fileExt) {
                // phpファイルはコピーしない
                continue;
            }

            if (file_exists($targetDir.'/'.$file)) {
                $beforeContents = file_get_contents($targetDir.'/'.$file);
                $afterContents = file_get_contents($originalDir.'/'.$file);

                if ($beforeContents === $afterContents) {
                    continue;
                }
            }

            // コンテンツに更新があった
            copy($originalDir.'/'.$file, $targetDir.'/'.$file) && chmod($targetDir.'/'.$file, 0666);

            $staticName = str_replace($this->staticPath, '', $targetDir.'/'.$file);
            $this->uploadList[] = $staticName;
        }
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

    private function getTargetFiles($path)
    {
        $uploadList = [];
        $noUploadList = [];
        $this->uploadList = array_unique($this->uploadList);
        foreach ($this->uploadList as $uploadFile) {
            if (strncmp($uploadFile, $path, strlen($path))) {
                // 対象外の更新ファイル
                $noUploadList[] = $uploadFile;
                continue;
            }

            $uploadList[] = $uploadFile;
        }

        $deleteList = [];
        $noDeleteList = [];
        $this->deleteList = array_unique($this->deleteList);
        foreach ($this->deleteList as $deleteFile) {
            if (strncmp($deleteFile, $path, strlen($path))) {
                // 対象外の削除ファイル
                $noDeleteList[] = $deleteFile;
                continue;
            }

            $deleteList[] = $deleteFile;
        }

        return [$uploadList, $deleteList, $noUploadList, $noDeleteList];
    }

    private function updateS3Disk($uploadList, $deleteList)
    {
        $s3Disk = \Storage::disk('s3');

        foreach ($uploadList as $uploadFile) {
            $s3Disk->putFileAs(dirname($uploadFile), $this->staticPath.$uploadFile, basename($uploadFile));

            echo "S3 Upload \"{$uploadFile}\""."\n";
        }

        foreach ($deleteList as $deleteFile) {
            $s3Disk->delete($deleteFile);

            echo "S3 Delete \"{$deleteFile}\""."\n";
        }

        return;
    }
}
