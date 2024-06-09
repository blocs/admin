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

        // テスト作成
        empty($developJson['controller']['modelName']) || $this->makeTest($developJson);

        // ドキュメント作成
        empty($developJson['controller']['controllerName']) || $this->makeDoc($developJson);
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
            $replaceItem['PREFIX'] = implode('.', $menuName);
        }

        if (!empty($developJson['controller']['loopItem'])) {
            $replaceItem['LOOP_ITEM'] = $developJson['controller']['loopItem'];
            $replaceItem['SINGULAR_ITEM'] = \Str::singular($developJson['controller']['loopItem']);
        }

        $replaceItem['HEAD_HTML'] = '';
        $replaceItem['BODY_HTML'] = '';

        $formHtml = file_get_contents(__DIR__.'/../../develop/form.blocs.html');
        $replaceItem['FORM_HTML'] = '';

        $blocsCompiler = new \Blocs\Compiler\BlocsCompiler();
        foreach ($developJson['entry'] as $formName => $form) {
            $replaceItem['HEAD_HTML'] .= '                        <!-- data-include="sortHeader" $sortItem="'.$formName.'" -->'."\n";
            $replaceItem['HEAD_HTML'] .= '                        <th>'."\n";
            $replaceItem['HEAD_HTML'] .= '                            <!-- data-include="sortHref" -->'."\n";
            $replaceItem['HEAD_HTML'] .= '                            <a class="dataTable-sorter">'.$form['label']."</a>\n";
            $replaceItem['HEAD_HTML'] .= '                        </th>'."\n";

            $replaceItem['BODY_HTML'] .= '                        <td class=""><!-- $'.$replaceItem['SINGULAR_ITEM'].'->'.$formName;
            'upload' === $form['type'] && $replaceItem['BODY_HTML'] .= ' data-convert="raw_download"';
            $replaceItem['BODY_HTML'] .= ' --></td>'."\n";

            $form['name'] = $formName;

            $form['option_'] = ('select' === $form['type'] ? [['value' => '', 'label' => '']] : []);
            if (!empty($form['option'])) {
                foreach ($form['option'] as $value => $label) {
                    $form['option_'][] = [
                        'value' => $value,
                        'label' => $label,
                    ];
                }
            }

            $replaceItem['FORM_HTML'] .= $blocsCompiler->render($formHtml, $form);
        }

        $this->copyDir(__DIR__.'/../../develop/views', $viewPath, $replaceItem);
    }

    private function makeTest($developJson)
    {
        $modelName = $developJson['controller']['modelName'];
        $testPath = base_path("tests/Feature/{$modelName}Test.php");

        if (file_exists($testPath)) {
            return;
        }

        $tests = file_get_contents(__DIR__.'/../../develop/tests.php');

        $tests = str_replace('TEST_NAME', "{$modelName}Test", $tests);

        foreach ($developJson['controller'] as $key => $value) {
            // キーを変換
            $key = strtoupper(Str::snake($key));
            $tests = str_replace($key, $value, $tests);
        }

        foreach ($developJson['route'] as $key => $value) {
            // キーを変換
            $key = strtoupper(Str::snake($key));
            $tests = str_replace($key, $value, $tests);
        }

        $formList = [];
        foreach ($developJson['entry'] as $formName => $form) {
            if ('datepicker' == $form['type']) {
                $formValue = '2024-06-10';
            } elseif ('select' == $form['type'] || 'select2' == $form['type'] || 'radio' == $form['type'] || 'checkbox' == $form['type']) {
                $formValue = '0';
            } elseif ('upload' == $form['type']) {
                $formValue = '';
            } else {
                $formValue = 'test';
            }

            $formList[] = "{$formName}' => '{$formValue}";
        }
        $tests = str_replace('FORM_LIST', $this->getList($formList, ",\n            ", "'"), $tests);

        file_put_contents($testPath, $tests);
        echo "Make test \"{$testPath}\"\n";
    }

    private function makeDoc($developJson)
    {
        $controllerName = $developJson['controller']['controllerName'];
        $docsPath = base_path("docs/{$controllerName}.php");

        if (file_exists($docsPath)) {
            return;
        }

        $docs = file_get_contents(__DIR__.'/../../develop/docs.php');

        empty($developJson['menu']['lang']) && $developJson['menu']['lang'] = '';
        $docs = str_replace('MENU_LANG', $developJson['menu']['lang'], $docs);

        foreach ($developJson['controller'] as $key => $value) {
            // キーを変換
            $key = strtoupper(Str::snake($key));
            $docs = str_replace($key, $value, $docs);
        }

        $formList = [];
        foreach ($developJson['entry'] as $formName => $form) {
            $formList[] = "{$formName}' => '".$form['label'];
        }
        $docs = str_replace('FORM_LIST', $this->getList($formList, ",\n        ", "'"), $docs);

        file_put_contents($docsPath, $docs);
        echo "Make doc \"{$docsPath}\"\n";
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
