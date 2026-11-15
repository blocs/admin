<?php

namespace App\Console\Commands;

use Blocs\Compiler\BlocsCompiler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class Develop extends Command
{
    /**
     * コンソールコマンドの署名。
     *
     * @var string
     */
    protected $signature = 'blocs:develop {path}';

    /**
     * コンソールコマンドの説明。
     *
     * @var string
     */
    protected $description = 'Develop application';

    /**
     * コマンドを実行する。
     */
    public function handle(): void
    {
        $path = $this->argument('path');
        if (! file_exists($path)) {
            return;
        }

        $developConfig = $this->loadDevelopConfiguration($path);
        if (empty($developConfig['controller'])) {
            return;
        }

        // コントローラーを生成し、必要なルーティングとメニューを整備
        empty($developConfig['controller']['controllerName']) || $this->createControllerFile($developConfig);

        // モデルを生成し、フォーム項目を反映
        empty($developConfig['controller']['modelName']) || $this->createModelFile($developConfig);

        // テーブル定義を生成し、マイグレーションを整備
        empty($developConfig['controller']['loopItem']) || $this->createMigrationFile($developConfig);

        // ビューを生成し、フォーム定義を差し込む
        empty($developConfig['controller']['viewPrefix']) || $this->createViewTemplates($developConfig);
    }

    private function createControllerFile(array $developConfig): void
    {
        $controllerName = $developConfig['controller']['controllerName'];
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");

        if (file_exists($controllerPath)) {
            return;
        }

        // コントローラーを生成します
        $controllerTemplate = file_get_contents(base_path('vendor/blocs/admin/develop/controller.php'));
        $controllerTemplate = $this->applySearchAndSortColumns($controllerTemplate, $developConfig['form']);
        $controllerContents = $this->applyPlaceholderValues($controllerTemplate, $developConfig['controller']);

        $this->ensureDirectoryExists(dirname($controllerPath));
        file_put_contents($controllerPath, $controllerContents);
        $this->outputCreationMessage('controller', $controllerPath);

        $this->formatWithPint($controllerPath);

        // ルートを追加します
        isset($developConfig['route']) && $this->createRouteDefinition($developConfig['route'], $developConfig['controller']);

        // メニューを更新します
        empty($developConfig['menu']) || $this->mergeMenuConfiguration($developConfig['menu']);
    }

    /**
     * 一覧の検索対象と並び替えできる列を、フォームの定義から埋め込む。
     * 検索はファイルと有効・無効の項目を除き、並び替えは一覧の見出しに出る全項目を対象にする。
     *
     * @param  array<string, array<string, mixed>>  $formConfig
     */
    private function applySearchAndSortColumns(string $controllerTemplate, array $formConfig): string
    {
        $sortColumns = array_keys($formConfig);
        $searchColumns = [];
        foreach ($formConfig as $formName => $form) {
            if (($form['type'] ?? '') === 'upload' || $formName === 'disabled_at') {
                continue;
            }

            $searchColumns[] = $formName;
        }

        $defaultSort = count($sortColumns) ? "['".$sortColumns[0]."' => 'asc']" : '[]';

        return str_replace(
            ['SEARCH_LIST', 'SORT_LIST', 'DEFAULT_SORT'],
            [
                $this->buildDelimitedList($searchColumns, ', ', "'"),
                $this->buildDelimitedList($sortColumns, ', ', "'"),
                $defaultSort,
            ],
            $controllerTemplate
        );
    }

    private function createRouteDefinition(array $routeConfig, array $controllerConfig): void
    {
        $routeTemplate = file_get_contents(base_path('vendor/blocs/admin/develop/route.php'));
        $routeContents = $this->applyPlaceholderValues($routeTemplate, $routeConfig);
        $routeContents = $this->applyPlaceholderValues($routeContents, $controllerConfig);

        file_put_contents(base_path('routes/web.php'), "\n".$routeContents, FILE_APPEND);

        $this->formatWithPint(base_path('routes/web.php'));

        Artisan::call('route:cache');
    }

    private function mergeMenuConfiguration(array $menuConfig): void
    {
        $configList = config('menu');
        empty($configList) && $configList = [];

        // メニュー設定をマージします
        $configList['root'][] = $menuConfig;

        $laravelMenuPath = config_path('menu.php');
        $code = "<?php\n\nreturn ".var_export($configList, true).";\n";
        file_put_contents($laravelMenuPath, $code) && chmod($laravelMenuPath, 0644);

        $this->formatWithPint($laravelMenuPath);
    }

    private function createModelFile(array $developConfig): void
    {
        $modelName = $developConfig['controller']['modelName'];
        $modelPath = app_path("Models/{$modelName}.php");

        if (file_exists($modelPath)) {
            return;
        }

        // モデルを生成します
        $modelTemplate = file_get_contents(base_path('vendor/blocs/admin/develop/model.php'));
        $modelTemplate = str_replace(
            'FORM_LIST',
            $this->buildDelimitedList(array_keys($developConfig['form']), ",\n        ", "'"),
            $modelTemplate
        );
        $modelContents = $this->applyPlaceholderValues($modelTemplate, $developConfig['controller']);

        $this->ensureDirectoryExists(dirname($modelPath));
        file_put_contents($modelPath, $modelContents);
        $this->outputCreationMessage('model', $modelPath);

        $this->formatWithPint($modelPath);
    }

    private function createMigrationFile(array $developConfig): void
    {
        $loopItem = $developConfig['controller']['loopItem'];
        $migrationPath = 'create_'.$loopItem.'_table.php';

        if ($migrations = glob(database_path('migrations/*_'.$migrationPath))) {
            $migrationPath = $migrations[0];
            if ($this->confirm('Migrate "'.basename($migrationPath).'" ?')) {
                // テーブル再作成
                Artisan::call('migrate:refresh', ['--path' => 'database/migrations/'.basename($migrationPath)]);
            }

            return;
        }

        $migrationPath = database_path('migrations/'.date('Y_m_d').'_'.sprintf('%06d', rand(0, 9999)).'_'.$migrationPath);

        // テーブル定義作成
        $migrationTemplate = file_get_contents(base_path('vendor/blocs/admin/develop/migration.php'));
        $migrationTemplate = str_replace('LOOP_ITEM', $loopItem, $migrationTemplate);

        $itemList = [];
        foreach ($developConfig['form'] as $formName => $form) {
            if ($form['type'] == 'textarea' || $form['type'] == 'upload') {
                $type = 'text';
            } elseif ($formName == 'disabled_at') {
                $type = 'timestamp';
                $form['required'] = false;
            } else {
                $type = 'string';
            }

            $item = '$table->'.$type.'('."'{$formName}'".')';
            empty($form['required']) && $item .= '->nullable()';
            $itemList[] = $item.';';
        }
        $migrationContents = str_replace('/* ITEM_LIST */', $this->buildDelimitedList($itemList, "\n            "), $migrationTemplate);

        file_put_contents($migrationPath, $migrationContents);
        $this->outputCreationMessage('migration', $migrationPath);

        $this->formatWithPint($migrationPath);

        // テーブルを作成します
        Artisan::call('migrate:refresh', ['--path' => 'database/migrations/'.basename($migrationPath)]);
    }

    private function createViewTemplates(array $developConfig, bool $refresh = false): void
    {
        $viewPrefix = $developConfig['controller']['viewPrefix'];
        $viewPath = resource_path('views/'.str_replace('.', '/', $viewPrefix));

        if (empty($developConfig['controller']['loopItem'])) {
            // 一覧画面のテンプレートはloopItemから変数名を組み立てるため、未指定では生成できない
            $this->error('Can not make view without "loopItem".');

            return;
        }

        // フォームの定義をテンプレートに反映します
        $placeholderValues = [];
        $placeholderValues['LOOP_ITEM'] = $developConfig['controller']['loopItem'];
        $placeholderValues['SINGULAR_ITEM'] = Str::singular($developConfig['controller']['loopItem']);

        $placeholderValues['HEAD_HTML'] = '';
        $placeholderValues['BODY_HTML'] = '';

        $formBlocsHtml = file_get_contents(base_path('vendor/blocs/admin/develop/form.html'));
        $placeholderValues['FORM_HTML'] = "\n";
        $formHtml = '';

        $showBlocsHtml = file_get_contents(base_path('vendor/blocs/admin/develop/show.html'));
        $placeholderValues['SHOW_HTML'] = "\n";
        $showHtml = '';

        $blocsCompiler = new BlocsCompiler;
        foreach ($developConfig['form'] as $formName => $form) {
            $form['name'] = $formName;
            isset($form['label']) || $form['label'] = '';

            $placeholderValues['HEAD_HTML'] .= '                            <!-- data-include="sortHeader" $sortItem="'.$formName.'" -->'."\n";
            $placeholderValues['HEAD_HTML'] .= '                            <th>'."\n";
            $placeholderValues['HEAD_HTML'] .= '                                <!-- data-include="sortHref" -->'."\n";
            $placeholderValues['HEAD_HTML'] .= '                                <a class="dataTable-sorter">'.$form['label']."</a>\n";
            $placeholderValues['HEAD_HTML'] .= '                            </th>'."\n";

            $placeholderValues['BODY_HTML'] .= '                            <td class=""><!-- $'.$placeholderValues['SINGULAR_ITEM'].'->'.$formName;
            $form['type'] === 'upload' && $placeholderValues['BODY_HTML'] .= ' data-convert="raw_download"';
            $placeholderValues['BODY_HTML'] .= ' --></td>'."\n";

            if (! in_array($form['type'], ['textarea', 'datepicker', 'timepicker', 'select', 'select2', 'radio', 'checkbox', 'upload', 'number'])) {
                $form['inputType'] = $form['type'];
                $form['type'] = 'text';
            }

            $form['option_'] = [];
            if (! empty($form['option'])) {
                foreach ($form['option'] as $value => $label) {
                    $form['option_'][] = [
                        'value' => $value,
                        'label' => $label,
                    ];
                }
            }

            $placeholderValues['FORM_HTML'] .= '        <!-- data-include="form_'.$formName.'" -->'."\n";
            $formHtml .= $blocsCompiler->render($formBlocsHtml, $form);

            $placeholderValues['SHOW_HTML'] .= '        <!-- data-include="show_'.$formName.'" -->'."\n";
            $showHtml .= $blocsCompiler->render($showBlocsHtml, $form);
        }

        if ($refresh) {
            foreach ([$viewPath.'/include/entry.html', $viewPath.'/include/form.html', $viewPath.'/include/show.html', $viewPath.'/show.blocs.html'] as $filePath) {
                is_file($filePath) && unlink($filePath);
            }
        }

        $this->copyDirectoryTree(base_path('vendor/blocs/admin/develop/views'), $viewPath, $placeholderValues);

        // 生成用テンプレートの中で目印にしていたコメントを、テンプレートエンジンの記法に戻して保存する
        foreach (['form.html' => $formHtml, 'show.html' => $showHtml] as $fileName => $html) {
            $filePath = $viewPath.'/include/'.$fileName;
            if (file_exists($filePath)) {
                continue;
            }

            file_put_contents($filePath, trim(str_replace('<#-- ', '<!-- ', $html))."\n");
            $this->outputCreationMessage('view', $filePath);
        }
    }

    private function copyDirectoryTree(string $sourceDirectory, string $destinationDirectory, array $placeholderValues): void
    {
        $this->ensureDirectoryExists($destinationDirectory);

        $fileList = scandir($sourceDirectory);
        foreach ($fileList as $fileName) {
            if (substr($fileName, 0, 1) == '.' && $fileName != '.gitkeep') {
                continue;
            }

            if (is_dir($sourceDirectory.'/'.$fileName)) {
                $this->copyDirectoryTree($sourceDirectory.'/'.$fileName, $destinationDirectory.'/'.$fileName, $placeholderValues);

                continue;
            }

            if (! file_exists($destinationDirectory.'/'.$fileName)) {
                copy($sourceDirectory.'/'.$fileName, $destinationDirectory.'/'.$fileName);
                $this->replaceViewPlaceholders($destinationDirectory.'/'.$fileName, $placeholderValues);
            }
        }
    }

    private function loadDevelopConfiguration(string $path): array
    {
        $developJson = json_decode(file_get_contents($path), true);

        // 「Admin/ArticleController」のような指定を、クラス名と名前空間の後半に分ける
        foreach (['controller', 'model'] as $classType) {
            $className = $developJson['controller'][$classType.'Name'] ?? '';
            if (empty($className)) {
                continue;
            }

            $developJson['controller'][$classType.'Basename'] = basename($className);
            $developJson['controller'][$classType.'Dirname'] = basename($className) == $className
                ? ''
                : '\\'.str_replace('/', '\\', dirname($className));
        }

        isset($developJson['form']) || $developJson['form'] = [];

        // 種類が省略された項目はテキスト入力として扱う
        foreach ($developJson['form'] as $formName => $form) {
            $developJson['form'][$formName]['type'] = $form['type'] ?? 'text';
        }

        return $developJson;
    }

    private function applyPlaceholderValues(string $template, array $values): string
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $key = strtoupper(Str::snake($key));
            $template = str_replace($key, $value, $template);
        }

        return $template;
    }

    private function ensureDirectoryExists(string $directoryPath): void
    {
        if (! is_dir($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }
    }

    private function buildDelimitedList(array $items, string $separator, string $quote = ''): string
    {
        if (! count($items)) {
            return '';
        }

        return "{$quote}".implode("{$quote}{$separator}{$quote}", $items)."{$quote}";
    }

    private function replaceViewPlaceholders(string $viewPath, array $placeholderValues): void
    {
        $contents = file_get_contents($viewPath);

        foreach ($placeholderValues as $key => $value) {
            $contents = str_replace('<!-- '.$key.' -->', $value, $contents);
        }

        file_put_contents($viewPath, $contents);
        $this->outputCreationMessage('view', $viewPath);
    }

    /**
     * Pintでコードを整形します
     */
    private function formatWithPint(string $path): void
    {
        exec(base_path('vendor/bin/pint').' '.escapeshellarg($path));
    }

    private function outputCreationMessage(string $type, string $path): void
    {
        $path = str_replace(base_path(), '', $path);

        $this->info("Make {$type} \"{$path}\"");
    }
}
