<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;

class Deploy extends Command
{
    protected $signature;
    protected $description;

    protected $rootDir;
    protected $baseDir;

    public function __construct($signature, $description)
    {
        $this->signature = $signature;
        $this->description = $description;

        $this->rootDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__).'/../../../../../'));
        $this->baseDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__).'/../../'));

        parent::__construct();
    }

    public function handle()
    {
        /* 言語ファイルをマージ */

        $this->appendLang($this->baseDir.'/lang');
    }

    /* Private function */

    private function appendLang($blocsLangDir)
    {
        if (!is_dir($blocsLangDir)) {
            return;
        }

        $laravelLangDir = $this->rootDir.'/resources/lang';
        is_dir($laravelLangDir) || mkdir($laravelLangDir, 0777, true) && chmod($laravelLangDir, 0777);

        $blocsLangFileList = scandir($blocsLangDir);
        foreach ($blocsLangFileList as $file) {
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