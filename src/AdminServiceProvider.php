<?php

namespace Blocs;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register()
    {
        // コマンドの登録
        $this->app->runningInConsole() && $this->registerBlocsCommand();
    }

    public function boot()
    {
        // 定数の読み込み
        is_file(app_path('Consts/Blocs.php')) && \App\Consts\Blocs::define();

        // ルーティング追加
        is_file(base_path('routes/admin.php')) && $this->loadRoutesFrom(base_path('routes/admin.php'));

        // 必要ファイルを登録
        $this->app->runningInConsole() && $this->registerPublish();
    }

    public function registerBlocsCommand()
    {
        $this->app->singleton('command.blocs.install', function ($app) {
            return new Commands\InstallAdmin;
        });

        $this->commands('command.blocs.install');

        $this->app->singleton('command.blocs.develop', function ($app) {
            return new Commands\Develop;
        });

        $this->commands('command.blocs.develop');

        $this->app->singleton('command.blocs.knowledge', function ($app) {
            return new Commands\Knowledge;
        });

        $this->commands('command.blocs.knowledge');
    }

    public function registerPublish()
    {
        $publishList = [];

        // appをpublish
        $publishList[base_path('vendor/blocs/admin/app')] = app_path();

        // configをpublish
        $publishList[base_path('vendor/blocs/admin/config')] = config_path();

        // databaseをpublish
        $publishList[base_path('vendor/blocs/admin/database')] = database_path();

        // publicをpublish
        $publishList[base_path('vendor/blocs/admin/public')] = public_path();

        // resourceをpublish
        $publishList[base_path('vendor/blocs/admin/resources')] = resource_path();

        // routesをpublish
        $publishList[base_path('vendor/blocs/admin/routes')] = base_path('routes');

        // docsをpublish
        $publishList[base_path('vendor/blocs/admin/docs')] = base_path('docs');

        // testsをpublish
        $publishList[base_path('vendor/blocs/admin/tests')] = base_path('tests');

        $this->publishes($publishList);
    }
}
