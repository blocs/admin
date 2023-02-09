<?php

namespace Blocs\Commands;

use App\Admin\Middleware\StaticGenerator;
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
        // publicをstaticにコピー
        $this->copyDir($this->publicPath, $this->staticPath);

        // staticの静的コンテンツを更新
        $this->updateStaticDir($this->staticPath);

        if (!empty(env('AWS_BUCKET'))) {
            // 更新のあったファイルを差分反映
            $s3Disk = \Storage::disk('s3');

            $this->uploadList = array_unique($this->uploadList);
            foreach ($this->uploadList as $uploadFile) {
                $s3Disk->putFileAs(dirname($uploadFile), $this->staticPath.$uploadFile, basename($uploadFile));

                echo <<< END_of_TEXT
S3 Upload "{$uploadFile}"

END_of_TEXT;
            }

            $this->deleteList = array_unique($this->deleteList);
            foreach ($this->deleteList as $deleteFile) {
                $s3Disk->delete($deleteFile);

                echo <<< END_of_TEXT
S3 Delete "{$deleteFile}"

END_of_TEXT;
            }
        }

        // 設定ファイルを掃除
        $this->staticGenerator->refreshBuildConfig();
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

            if ('_build.json' === basename($publicFile)) {
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

                    echo <<< END_of_TEXT
Delete "{$staticName}"

END_of_TEXT;

                    continue;
                }

                $afterContents = file_get_contents($staticFile);
                if ($beforeContents !== $afterContents) {
                    // コンテンツに更新があった
                    $this->uploadList[] = $staticName;

                    echo <<< END_of_TEXT
Update "{$staticName}"

END_of_TEXT;
                }
                continue;
            }

            echo <<< END_of_TEXT
\e[7;31mNot found "{$staticName}"\e[m

END_of_TEXT;
        }
    }

    private function copyDir($originalDir, $targetDir)
    {
        if (!(is_dir($originalDir) && $fileList = scandir($originalDir))) {
            echo <<< END_of_TEXT
\e[7;31mNot found "{$originalDir}"\e[m

END_of_TEXT;

            exit;
        }

        is_dir($targetDir) || mkdir($targetDir, 0777, true) && chmod($targetDir, 0777);

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
}
