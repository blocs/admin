<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;

class Deploy extends Command
{
    protected $signature;
    protected $description;

    protected $baseDir;

    public function __construct($signature, $description, $filePath)
    {
        $this->signature = $signature;
        $this->description = $description;

        $this->baseDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname($filePath).'/../'));

        parent::__construct();
    }

    public function handle()
    {
        // 言語設定をマージ
        $this->appendLang($this->baseDir.'/lang');

        // メニュー設定をマージ
        $this->appendMenu($this->baseDir.'/config/menu.json');
    }

    private function appendLang($blocsLangDir)
    {
        if (!is_dir($blocsLangDir)) {
            return;
        }

        $laravelLangDir = resource_path('lang');
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

            file_put_contents($targetFile, json_encode($langJsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n") && chmod($targetFile, 0666);
        }
    }

    private function appendMenu($blocsMenuPath)
    {
        if (!file_exists($blocsMenuPath)) {
            return;
        }

        $blocsJson = json_decode(file_get_contents($blocsMenuPath), true);
        if (empty($blocsJson)) {
            return;
        }

        $laravelMenuPath = config_path('menu.json');
        if (file_exists($laravelMenuPath)) {
            $configJson = json_decode(file_get_contents(config_path('menu.json')), true);
        }
        empty($configJson) && $configJson = [];

        // メニュー設定をマージ
        foreach ($blocsJson as $menuName => $config) {
            if (empty($configJson[$menuName])) {
                $configJson[$menuName] = $config;
            } else {
                $configJson[$menuName] = array_merge($configJson[$menuName], $config);
            }
        }

        file_put_contents($laravelMenuPath, json_encode($configJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) && chmod($laravelMenuPath, 0666);
    }
}
