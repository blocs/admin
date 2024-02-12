<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class Develop extends Command
{
    protected $signature;
    protected $description;

    public function __construct($signature, $description)
    {
        $this->signature = $signature;
        $this->description = $description;

        parent::__construct();
    }

    public function handle()
    {
        $path = $this->argument('path');
        if (!file_exists($path)) {
            return;
        }

        $developJson = json_decode(file_get_contents($path), true);
        if (empty($developJson['controller'])) {
            return;
        }

        isset($developJson['entry']) || $developJson['entry'] = [];

        // コントローラー作成
        empty($developJson['controller']['controllerName']) || $this->makeController($developJson);

        // モデル作成
        empty($developJson['controller']['modelName']) || $this->makeModel($developJson);

        // テーブル定義作成
        empty($developJson['controller']['loopItem']) || $this->makeMigration($developJson);

        // ビュー作成
        empty($developJson['controller']['viewPrefix']) || $this->makeView($developJson);
    }

    private function makeController($developJson)
    {
        $controllerName = $developJson['controller']['controllerName'];
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");

        if (file_exists($controllerPath)) {
            return;
        }

        // コントローラー作成
        $controller = file_get_contents(__DIR__.'/../../develop/controller.php');
        foreach ($developJson['controller'] as $key => $value) {
            // キーを変換
            $key = strtoupper(Str::snake($key));
            $controller = str_replace($key, $value, $controller);
        }

        file_put_contents($controllerPath, $controller);
        echo "Make controller \"{$controllerName}\"\n";

        // ルート作成
        isset($developJson['route']) && $this->makeRoute($developJson['route'], $controllerName);

        // メニュー作成
        empty($developJson['menu']) || $this->appendMenu($developJson['menu']);
    }

    private function makeRoute($routeJson, $controllerName)
    {
        $route = file_get_contents(__DIR__.'/../../develop/route.php');
        foreach ($routeJson as $key => $value) {
            // キーを変換
            $key = strtoupper(Str::snake($key));
            $route = str_replace($key, $value, $route);
        }

        $route = str_replace('CONTROLLER_NAME', $controllerName, $route);

        file_put_contents(base_path('routes/web.php'), "\n".$route, FILE_APPEND);
    }

    private function appendMenu($menuJson)
    {
        $configJson = [];
        $laravelMenuPath = config_path('menu.json');
        if (file_exists($laravelMenuPath)) {
            $configJson = json_decode(file_get_contents($laravelMenuPath), true);
        }
        empty($configJson) && $configJson = [];

        // メニュー設定をマージ
        $configJson['root'][] = $menuJson;

        file_put_contents($laravelMenuPath, json_encode($configJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
    }

    private function makeModel($developJson)
    {
        $modelName = $developJson['controller']['modelName'];
        $modelPath = app_path("Models/{$modelName}.php");

        if (file_exists($modelPath)) {
            return;
        }

        // モデル作成
        $model = file_get_contents(__DIR__.'/../../develop/model.php');
        $model = str_replace('MODEL_NAME', $modelName, $model);
        $model = str_replace('FORM_LIST', $this->getList(array_keys($developJson['entry']), ",\n        ", "'"), $model);

        file_put_contents($modelPath, $model);
        echo "Make model \"{$modelName}\"\n";
    }

    private function makeMigration($developJson)
    {
        $loopItem = $developJson['controller']['loopItem'];
        $migrationPath = 'create_'.$loopItem.'_table.php';

        if ($migrations = glob(database_path('migrations').'/*_'.$migrationPath)) {
            $migrationPath = $migrations[0];
            echo 'Migrate "'.basename($migrationPath).'" ? ';

            if ('y' === trim(strtolower(fgets(STDIN)))) {
                // テーブル再作成
                \Artisan::call('migrate:refresh', ['--path' => 'database/migrations/'.basename($migrationPath)]);
            }

            return;
        }

        $migrationPath = database_path('migrations').'/'.date('Y_m_d').'_000000_'.$migrationPath;

        // テーブル定義作成
        $migration = file_get_contents(__DIR__.'/../../develop/migration.php');
        $migration = str_replace('LOOP_ITEM', $loopItem, $migration);

        $itemList = [];
        foreach ($developJson['entry'] as $formName => $form) {
            if ('textarea' == $form['type'] || 'upload' == $form['type']) {
                $type = 'text';
            } else {
                $type = 'string';
            }

            $item = '$table->'.$type.'('."'{$formName}'".')';
            empty($form['required']) && $item .= '->nullable()';
            $itemList[] = $item.';';
        }
        $migration = str_replace('/* ITEM_LIST */', $this->getList($itemList, "\n            "), $migration);

        file_put_contents($migrationPath, $migration);
        echo 'Make migration "'.basename($migrationPath)."\"\n";

        // テーブル作成
        \Artisan::call('migrate:refresh', ['--path' => 'database/migrations/'.basename($migrationPath)]);

        // テープル定義表示
        $this->makeDiagram($developJson, $loopItem);
    }

    private function makeDiagram($developJson)
    {
        $loopItem = $developJson['controller']['loopItem'];

        // テープル定義表示
        $database = file_get_contents(__DIR__.'/../../develop/database.pu');

        if (empty($developJson['controller']['modelName'])) {
            $className = $loopItem;
        } else {
            $className = $developJson['controller']['modelName'].' | '.$loopItem;
        }
        $database = str_replace('CLASS_NAME', $className, $database);
        $database = str_replace('LOOP_ITEM', $loopItem, $database);
        $database = str_replace('FORM_LIST', $this->getList(array_keys($developJson['entry']), "\n        "), $database);

        empty($developJson['menu']['lang']) && $developJson['menu']['lang'] = '';
        $database = str_replace('MENU_LANG', $developJson['menu']['lang'], $database);

        echo $database;
    }

    private function makeView($developJson)
    {
        $viewPrefix = $developJson['controller']['viewPrefix'];
        $viewPath = resource_path('views/'.str_replace('.', '/', $viewPrefix));

        // フォームの追加
        $replaceItem = [];
        if (!empty($developJson['menu']['name'])) {
            $replaceItem['MENU_NAME'] = $developJson['menu']['name'];

            $menuName = explode('.', $replaceItem['MENU_NAME']);
            array_pop($menuName);
            $replaceItem['EDIT_NAME'] = implode('.', $menuName).'.edit';
            $replaceItem['SELECT_NAME'] = implode('.', $menuName).'.select';
        }

        if (!empty($developJson['controller']['loopItem'])) {
            $replaceItem['LOOP_ITEM'] = $developJson['controller']['loopItem'];
            $replaceItem['SINGULAR_ITEM'] = \Str::singular($developJson['controller']['loopItem']);
        }

        $replaceItem['HEAD_HTML'] = '';
        $replaceItem['BODY_HTML'] = '';

        $noticeItem = $developJson['controller']['noticeItem'] ?? '';
        $formHtml = file_get_contents(__DIR__.'/../../develop/form.blocs.html');
        $replaceItem['FORM_HTML'] = '';

        $blocsCompiler = new \Blocs\Compiler\BlocsCompiler();
        foreach ($developJson['entry'] as $formName => $form) {
            $replaceItem['HEAD_HTML'] .= "        <th class='col-xs-1'>".$form['label'].'</th>'."\n";

            $replaceItem['BODY_HTML'] .= '        <td data-val=$'.$replaceItem['SINGULAR_ITEM'].'->'.$formName;
            'upload' === $form['type'] && $replaceItem['BODY_HTML'] .= " data-convert='raw_upload'";
            $replaceItem['BODY_HTML'] .= '></td>'."\n";

            $form['name'] = $formName;
            $form['noticeItem'] = ($noticeItem == $formName);

            if (!empty($form['option'])) {
                $form['options'] = [];
                foreach ($form['option'] as $value => $label) {
                    $form['options'][] = [
                        'value' => $value,
                        'label' => $label,
                    ];
                }
            }

            $replaceItem['FORM_HTML'] .= $blocsCompiler->template($formHtml, $form);
        }

        $this->copyDir(__DIR__.'/../../develop/views', $viewPath, $replaceItem);
    }

    private function getList($form, $separator, $quote = '')
    {
        if (!count($form)) {
            return '';
        }

        return "{$quote}".implode("{$quote}{$separator}{$quote}", $form)."{$quote}";
    }

    private function copyDir($orgDir, $targetDir, $replaceItem)
    {
        is_dir($targetDir) || mkdir($targetDir, 0777, true);

        $fileList = scandir($orgDir);
        foreach ($fileList as $file) {
            if ('.' == substr($file, 0, 1) && '.gitkeep' != $file) {
                continue;
            }

            if (is_dir($orgDir.'/'.$file)) {
                $this->copyDir($orgDir.'/'.$file, $targetDir.'/'.$file, $replaceItem);
                continue;
            }

            if (!file_exists($targetDir.'/'.$file)) {
                copy($orgDir.'/'.$file, $targetDir.'/'.$file);
                $this->replaceViewItem($targetDir.'/'.$file, $replaceItem);

                echo "Make view \"{$targetDir}/{$file}\"\n";
            }
        }
    }

    private function replaceViewItem($viewPath, $replaceItem)
    {
        $contents = file_get_contents($viewPath);

        foreach ($replaceItem as $key => $value) {
            $contents = str_replace('<!-- '.$key.' -->', $value, $contents);
        }

        $contents = str_replace('<#-- ', '<!-- ', $contents);

        file_put_contents($viewPath, $contents);
    }
}
