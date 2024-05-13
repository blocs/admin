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

        // 空のfaviconがあれば削除
        $faviconPath = public_path('favicon.ico');
        file_exists($faviconPath) && !filesize($faviconPath) && unlink($faviconPath);

        // 必要ファイルをpublish
        \Artisan::call('vendor:publish', ['--provider' => 'Blocs\AdminServiceProvider']);

        // 初期ユーザー登録
        \Artisan::call('migrate');
        \Artisan::call('db:seed', ['--class' => 'AdminSeeder']);

        echo "Deploy was completed successfully.\n";

        \Artisan::call('route:cache');
        echo 'Login URL is '.route('login').".\n";
        echo "Initial ID/Pass is admin/admin.\n";
        \Artisan::call('route:clear');
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

        $configJson = [];
        $laravelMenuPath = config_path('menu.json');
        if (file_exists($laravelMenuPath)) {
            $configJson = json_decode(file_get_contents($laravelMenuPath), true);
        }
        empty($configJson) && $configJson = [];

        // メニュー設定をマージ
        foreach ($blocsJson as $menuName => $config) {
            if (empty($configJson[$menuName])) {
                $configJson[$menuName] = $config;
                continue;
            }

            $menuNameList = [];
            foreach ($configJson[$menuName] as $menu) {
                $menuNameList[] = $menu['name'];
            }

            foreach ($config as $menu) {
                in_array($menu['name'], $menuNameList) || $configJson[$menuName][] = $menu;
            }
        }

        file_put_contents($laravelMenuPath, json_encode($configJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n") && chmod($laravelMenuPath, 0666);
    }
}
