<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Blocs extends Command
{
    protected $signature;
    protected $description;

    protected $rootDir;
    protected $stubDir;

    public function __construct($signature, $description, $fileLoc)
    {
        $this->signature = $signature;
        $this->description = $description;

        $this->rootDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname($fileLoc).'/../../../../'));
        $this->stubDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname($fileLoc).'/../stubs'));

        parent::__construct();
    }

    public function handle()
    {
        /* 言語ファイルをマージ */

        $this->mergeLang($this->stubDir.'/../lang');

        /* アップデート状況把握のため更新情報を取得 */

        $fileLoc = $this->rootDir.'/storage/blocs_update.json';
        if (is_file($fileLoc)) {
            $updateJsonData = json_decode(file_get_contents($fileLoc), true);
        } else {
            $updateJsonData = [];
        }

        /* ディレクトリを配置 */

        $files = scandir($this->stubDir);
        foreach ($files as $file) {
            if ('.' == substr($file, 0, 1) && '.gitkeep' != $file && '.htaccess' != $file) {
                continue;
            }

            if (!is_dir($this->stubDir.'/'.$file)) {
                continue;
            }

            $targetDir = $file;
            $updateJsonData = $this->copyDir($this->stubDir.'/'.$targetDir, $this->rootDir.'/'.$targetDir, $updateJsonData);
            echo <<< END_of_TEXT
Deploy "{$targetDir}"

END_of_TEXT;
        }

        ksort($updateJsonData);
        file_put_contents($fileLoc, json_encode($updateJsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) && chmod($fileLoc, 0666);
    }

    /* Private function */

    private function copyDir($dirName, $newDir, $updateJsonData)
    {
        is_dir($newDir) || mkdir($newDir, 0777, true) && chmod($newDir, 0777);

        if (!(is_dir($dirName) && $files = scandir($dirName))) {
            return $updateJsonData;
        }

        foreach ($files as $file) {
            if ('.' == substr($file, 0, 1) && '.gitkeep' != $file && '.htaccess' != $file) {
                continue;
            }

            if (is_dir($dirName.'/'.$file)) {
                $updateJsonData = $this->copyDir($dirName.'/'.$file, $newDir.'/'.$file, $updateJsonData);
            } else {
                $updateJsonData = $this->copyFile($dirName.'/'.$file, $newDir.'/'.$file, $updateJsonData);
            }
        }

        return $updateJsonData;
    }

    private function copyFile($originalFile, $targetFile, $updateJsonData)
    {
        $originalFile = str_replace(DIRECTORY_SEPARATOR, '/', realpath($originalFile));
        $newContents = file_get_contents($originalFile);
        $fileKey = substr($targetFile, strlen($this->rootDir));

        if (!is_file($targetFile) || !filesize($targetFile)) {
            // コピー先にファイルがない
            if (!empty($updateJsonData[$fileKey])) {
                // ファイルを意図的に消した時はコピーしない
                return $updateJsonData;
            }

            file_put_contents($targetFile, $newContents) && chmod($targetFile, 0666);
            $targetFile = str_replace(DIRECTORY_SEPARATOR, '/', realpath($targetFile));
            $updateJsonData[$fileKey] = md5($newContents);

            return $updateJsonData;
        }

        // コピー先にファイルがある
        $targetFile = str_replace(DIRECTORY_SEPARATOR, '/', realpath($targetFile));
        $oldContents = file_get_contents($targetFile);

        if ($newContents === $oldContents) {
            // ファイルが更新されていない
            $updateJsonData[$fileKey] = md5($newContents);

            return $updateJsonData;
        }

        if (isset($updateJsonData[$fileKey]) && $updateJsonData[$fileKey] === md5($oldContents)) {
            // ファイルが更新された
            file_put_contents($targetFile, $newContents) && chmod($targetFile, 0666);
            $updateJsonData[$fileKey] = md5($newContents);

            return $updateJsonData;
        }

        // 違う内容のファイルがある
        echo <<< END_of_TEXT
\e[7;31m"{$targetFile}" already exists.\e[m

END_of_TEXT;

        return $updateJsonData;
    }

    private function mergeLang($blocsLangDir)
    {
        if (!is_dir($blocsLangDir)) {
            return;
        }

        $laravelLangDir = $this->rootDir.'/resources/lang';
        is_dir($laravelLangDir) || mkdir($laravelLangDir, 0777, true) && chmod($laravelLangDir, 0777);

        $blocsLangFiles = scandir($blocsLangDir);
        foreach ($blocsLangFiles as $file) {
            if ('.' == substr($file, 0, 1) && '.gitkeep' != $file && '.htaccess' != $file) {
                continue;
            }

            $targetFile = $laravelLangDir.'/'.$file;
            if (!is_file($targetFile)) {
                // ファイルがないのでコピー
                copy($blocsLangDir.'/'.$file, $targetFile) && chmod($targetFile, 0666);
                continue;
            }

            // ファイルをマージ
            $langJsonData = json_decode(file_get_contents($targetFile), true);
            $langJsonData = array_merge($langJsonData, json_decode(file_get_contents($blocsLangDir.'/'.$file), true));
            ksort($langJsonData);

            file_put_contents($targetFile, json_encode($langJsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) && chmod($targetFile, 0666);
        }
    }
}
