<?php

namespace Blocs;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register()
    {
        // コンソールコマンドの登録処理を実行
        $this->app->runningInConsole() && $this->registerBlocsCommand();
    }

    public function boot()
    {
        // アプリケーション定数の読み込みを実行
        is_file(app_path('Consts/Blocs.php')) && \App\Consts\Blocs::define();

        // 管理画面用のルーティングを追加
        is_file(base_path('routes/admin.php')) && $this->loadRoutesFrom(base_path('routes/admin.php'));

        // Artisan publish 対象のファイルを登録
        $this->app->runningInConsole() && $this->registerPublish();
    }

    public function registerBlocsCommand()
    {
        $consoleCommandBindings = [
            'command.blocs.install' => Commands\InstallAdmin::class,
            'command.blocs.develop' => Commands\Develop::class,
            'command.blocs.knowledge' => Commands\Knowledge::class,
        ];

        foreach ($consoleCommandBindings as $consoleCommandKey => $consoleCommandClass) {
            $this->registerConsoleCommand($consoleCommandKey, $consoleCommandClass);
        }
    }

    public function registerPublish()
    {
        $this->publishes($this->buildPublishMappings());
    }

    private function registerConsoleCommand(string $consoleCommandKey, string $consoleCommandClass): void
    {
        $this->app->singleton($consoleCommandKey, function () use ($consoleCommandClass) {
            return new $consoleCommandClass;
        });

        $this->commands($consoleCommandKey);
    }

    private function buildPublishMappings(): array
    {
        $publishMappings = [];

        // appをpublish
        $publishMappings[base_path('vendor/blocs/admin/app')] = app_path();

        // configをpublish
        $publishMappings[base_path('vendor/blocs/admin/config')] = config_path();

        // databaseをpublish
        $publishMappings[base_path('vendor/blocs/admin/database')] = database_path();

        // publicをpublish
        $publishMappings[base_path('vendor/blocs/admin/public')] = public_path();

        // resourceをpublish
        $publishMappings[base_path('vendor/blocs/admin/resources')] = resource_path();

        // routesをpublish
        $publishMappings[base_path('vendor/blocs/admin/routes')] = base_path('routes');

        // docsをpublish
        $publishMappings[base_path('vendor/blocs/admin/docs')] = base_path('docs');

        // testsをpublish
        $publishMappings[base_path('vendor/blocs/admin/tests')] = base_path('tests');

        return $publishMappings;
    }
}
