<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class Develop extends Command
{
    use DevelopTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocs:develop {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Develop application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->argument('path');
        if (!file_exists($path)) {
            return;
        }

        $developJson = $this->refreshDevelopJson($path);
        if (empty($developJson['controller'])) {
            return;
        }

        // コントローラー作成
        empty($developJson['controller']['controllerName']) || $this->makeController($developJson);

        // モデル作成
        empty($developJson['controller']['modelName']) || $this->makeModel($developJson);

        // テーブル定義作成
        empty($developJson['controller']['loopItem']) || $this->makeMigration($developJson);

        // ビュー作成
        empty($developJson['controller']['viewPrefix']) || $this->makeView($developJson);

        // ドキュメント作成
        empty($developJson['controller']['controllerName']) || $this->makeDoc($developJson);

        // 修正
        empty($developJson['controller']['viewPrefix']) || $this->refreshView($path);
    }

    private function makeController($developJson)
    {
        $controllerName = $developJson['controller']['controllerName'];
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");

        if (file_exists($controllerPath)) {
            return;
        }

        // コントローラー作成
        $controller = file_get_contents(base_path('vendor/blocs/admin/develop/controller.php'));
        foreach ($developJson['controller'] as $key => $value) {
            // キーを変換
            $key = strtoupper(Str::snake($key));
            $controller = str_replace($key, $value, $controller);
        }

        is_dir(dirname($controllerPath)) || mkdir(dirname($controllerPath), 0777, true);
        file_put_contents($controllerPath, $controller);
        $this->outputMessageMake('controller', $controllerPath);

        // ルート作成
        isset($developJson['route']) && $this->makeRoute($developJson['route'], $developJson['controller']);

        // メニュー作成
        empty($developJson['menu']) || $this->appendMenu($developJson['menu']);
    }

    private function makeRoute($routeJson, $controllerJson)
    {
        $route = file_get_contents(base_path('vendor/blocs/admin/develop/route.php'));
        foreach ($routeJson as $key => $value) {
            // キーを変換
            $key = strtoupper(Str::snake($key));
            $route = str_replace($key, $value, $route);
        }

        foreach ($controllerJson as $key => $value) {
            // キーを変換
            $key = strtoupper(Str::snake($key));
            $route = str_replace($key, $value, $route);
        }

        file_put_contents(base_path('routes/web.php'), "\n".$route, FILE_APPEND);

        \Artisan::call('route:cache');
    }

    private function appendMenu($menuJson)
    {
        $configList = config('menu');
        empty($configList) && $configList = [];

        // メニュー設定をマージ
        $configList['root'][] = $menuJson;

        $laravelMenuPath = config_path('menu.php');
        $code = "<?php\n\nreturn ".var_export($configList, true).";\n";
        file_put_contents($laravelMenuPath, $code) && chmod($laravelMenuPath, 0666);
    }

    private function makeModel($developJson)
    {
        $modelName = $developJson['controller']['modelName'];
        $modelPath = app_path("Models/{$modelName}.php");

        if (file_exists($modelPath)) {
            return;
        }

        // モデル作成
        $model = file_get_contents(base_path('vendor/blocs/admin/develop/model.php'));
        $model = str_replace('FORM_LIST', $this->getList(array_keys($developJson['form']), ",\n        ", "'"), $model);

        foreach ($developJson['controller'] as $key => $value) {
            // キーを変換
            $key = strtoupper(Str::snake($key));
            $model = str_replace($key, $value, $model);
        }

        is_dir(dirname($modelPath)) || mkdir(dirname($modelPath), 0777, true);
        file_put_contents($modelPath, $model);
        $this->outputMessageMake('model', $modelPath);
    }

    private function makeMigration($developJson)
    {
        $loopItem = $developJson['controller']['loopItem'];
        $migrationPath = 'create_'.$loopItem.'_table.php';

        if ($migrations = glob(database_path('migrations/*_'.$migrationPath))) {
            $migrationPath = $migrations[0];
            if ($this->confirm('Migrate "'.basename($migrationPath).'" ?')) {
                // テーブル再作成
                \Artisan::call('migrate:refresh', ['--path' => 'database/migrations/'.basename($migrationPath)]);
            }

            return;
        }

        $migrationPath = database_path('migrations/'.date('Y_m_d').'_'.sprintf('%06d', rand(0, 9999)).'_'.$migrationPath);

        // テーブル定義作成
        $migration = file_get_contents(base_path('vendor/blocs/admin/develop/migration.php'));
        $migration = str_replace('LOOP_ITEM', $loopItem, $migration);

        $itemList = [];
        foreach ($developJson['form'] as $formName => $form) {
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
        $this->outputMessageMake('migration', $migrationPath);

        // テーブル作成
        \Artisan::call('migrate:refresh', ['--path' => 'database/migrations/'.basename($migrationPath)]);

        // テープル定義表示
        $this->makeDiagram($developJson, $loopItem);
    }

    private function makeDiagram($developJson)
    {
        $loopItem = $developJson['controller']['loopItem'];

        // テープル定義表示
        $database = file_get_contents(base_path('vendor/blocs/admin/develop/database.pu'));

        if (empty($developJson['controller']['modelName'])) {
            $className = $loopItem;
        } else {
            $className = $developJson['controller']['modelName'].' | '.$loopItem;
        }
        $database = str_replace('CLASS_NAME', $className, $database);
        $database = str_replace('LOOP_ITEM', $loopItem, $database);
        $database = str_replace('FORM_LIST', $this->getList(array_keys($developJson['form']), "\n        "), $database);

        empty($developJson['menu']['lang']) && $developJson['menu']['lang'] = '';
        $database = str_replace('MENU_LANG', $developJson['menu']['lang'], $database);

        echo $database;
    }

    private function makeView($developJson, $refresh = false)
    {
        $viewPrefix = $developJson['controller']['viewPrefix'];
        $viewPath = resource_path('views/'.str_replace('.', '/', $viewPrefix));

        // フォームの追加
        $replaceItem = [];
        if (!empty($developJson['controller']['loopItem'])) {
            $replaceItem['LOOP_ITEM'] = $developJson['controller']['loopItem'];
            $replaceItem['SINGULAR_ITEM'] = \Str::singular($developJson['controller']['loopItem']);
        }

        $replaceItem['HEAD_HTML'] = '';
        $replaceItem['BODY_HTML'] = '';

        $formBlocsHtml = file_get_contents(base_path('vendor/blocs/admin/develop/form.html'));
        $replaceItem['FORM_HTML'] = "\n";
        $formHtml = '';

        $showBlocsHtml = file_get_contents(base_path('vendor/blocs/admin/develop/show.html'));
        $replaceItem['SHOW_HTML'] = "\n";
        $showHtml = '';

        $blocsCompiler = new \Blocs\Compiler\BlocsCompiler();
        foreach ($developJson['form'] as $formName => $form) {
            $form['name'] = $formName;
            isset($form['label']) || $form['label'] = '';

            $replaceItem['HEAD_HTML'] .= '                            <!-- data-include="sortHeader" $sortItem="'.$formName.'" -->'."\n";
            $replaceItem['HEAD_HTML'] .= '                            <th>'."\n";
            $replaceItem['HEAD_HTML'] .= '                                <!-- data-include="sortHref" -->'."\n";
            $replaceItem['HEAD_HTML'] .= '                                <a class="dataTable-sorter">'.$form['label']."</a>\n";
            $replaceItem['HEAD_HTML'] .= '                            </th>'."\n";

            $replaceItem['BODY_HTML'] .= '                            <td class=""><!-- $'.$replaceItem['SINGULAR_ITEM'].'->'.$formName;
            'upload' === $form['type'] && $replaceItem['BODY_HTML'] .= ' data-convert="raw_download"';
            $replaceItem['BODY_HTML'] .= ' --></td>'."\n";

            if (!in_array($form['type'], ['textarea', 'datepicker', 'timepicker', 'select', 'select2', 'radio', 'checkbox', 'upload', 'number'])) {
                $form['inputType'] = $form['type'];
                $form['type'] = 'text';
            }

            $form['option_'] = [];
            if (!empty($form['option'])) {
                foreach ($form['option'] as $value => $label) {
                    $form['option_'][] = [
                        'value' => $value,
                        'label' => $label,
                    ];
                }
            }

            $replaceItem['FORM_HTML'] .= '        <!-- data-include="form_'.$formName.'" -->'."\n";
            $formHtml .= $blocsCompiler->render($formBlocsHtml, $form);

            $replaceItem['SHOW_HTML'] .= '        <!-- data-include="show_'.$formName.'" -->'."\n";
            $showHtml .= $blocsCompiler->render($showBlocsHtml, $form);
        }

        if ($refresh) {
            foreach ([$viewPath.'/include/entry.html', $viewPath.'/include/form.html', $viewPath.'/include/show.html', $viewPath.'/show.blocs.html'] as $file) {
                unlink($file);
            }
        }

        $this->copyDir(base_path('vendor/blocs/admin/develop/views'), $viewPath, $replaceItem);

        if (!file_exists($viewPath.'/include/form.html')) {
            $formHtml = str_replace('<#-- ', '<!-- ', $formHtml);

            file_put_contents($viewPath.'/include/form.html', trim($formHtml)."\n");
            $this->outputMessageMake('view', $viewPath.'/include/form.html');
        }

        if (!file_exists($viewPath.'/include/show.html')) {
            $showHtml = str_replace('<#-- ', '<!-- ', $showHtml);

            file_put_contents($viewPath.'/include/show.html', trim($showHtml)."\n");
            $this->outputMessageMake('view', $viewPath.'/include/show.html');
        }
    }

    private function makeDoc($developJson)
    {
        $controllerName = $developJson['controller']['controllerBasename'];
        $docsPath = base_path("docs/{$controllerName}.php");

        if (!is_dir(base_path('docs')) || file_exists($docsPath)) {
            return;
        }

        $docs = file_get_contents(base_path('vendor/blocs/admin/develop/docs.php'));

        empty($developJson['menu']['lang']) && $developJson['menu']['lang'] = '';
        $docs = str_replace('MENU_LANG', $developJson['menu']['lang'], $docs);

        foreach ($developJson['controller'] as $key => $value) {
            // キーを変換
            $key = strtoupper(Str::snake($key));
            $docs = str_replace($key, $value, $docs);
        }

        $formList = [];
        foreach ($developJson['form'] as $formName => $form) {
            isset($form['label']) && $formList[] = "{$formName}' => '".$form['label'];
        }
        $docs = str_replace('FORM_LIST', $this->getList($formList, ",\n        ", "'"), $docs);

        file_put_contents($docsPath, $docs);
        $this->outputMessageMake('doc', $docsPath);
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
            }
        }
    }

    private function replaceViewItem($viewPath, $replaceItem)
    {
        $contents = file_get_contents($viewPath);

        foreach ($replaceItem as $key => $value) {
            $contents = str_replace('<!-- '.$key.' -->', $value, $contents);
        }

        file_put_contents($viewPath, $contents);
        $this->outputMessageMake('view', $viewPath);
    }

    private function outputMessageMake($type, $path)
    {
        $path = str_replace(base_path(), '', $path);

        $this->info("Make {$type} \"{$path}\"");
    }

    private function refreshDevelopJson($path)
    {
        $developJson = json_decode(file_get_contents($path), true);

        if (!empty($developJson['controller']['controllerName'])) {
            $developJson['controller']['controllerBasename'] = basename($developJson['controller']['controllerName']);

            if ($developJson['controller']['controllerBasename'] == $developJson['controller']['controllerName']) {
                $developJson['controller']['controllerDirname'] = '';
            } else {
                $developJson['controller']['controllerDirname'] = dirname($developJson['controller']['controllerName']);
                $developJson['controller']['controllerDirname'] = str_replace('/', '\\', $developJson['controller']['controllerDirname']);
                $developJson['controller']['controllerDirname'] = '\\'.$developJson['controller']['controllerDirname'];
            }
        }

        if (!empty($developJson['controller']['modelName'])) {
            $developJson['controller']['modelBasename'] = basename($developJson['controller']['modelName']);

            if ($developJson['controller']['modelBasename'] == $developJson['controller']['modelName']) {
                $developJson['controller']['modelDirname'] = '';
            } else {
                $developJson['controller']['modelDirname'] = dirname($developJson['controller']['modelName']);
                $developJson['controller']['modelDirname'] = str_replace('/', '\\', $developJson['controller']['modelDirname']);
                $developJson['controller']['modelDirname'] = '\\'.$developJson['controller']['modelDirname'];
            }
        }

        isset($developJson['form']) || $developJson['form'] = [];

        return $developJson;
    }
}
