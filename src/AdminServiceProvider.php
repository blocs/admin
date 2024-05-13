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
        $this->app->singleton('command.blocs.admin', function ($app) {
            return new \Blocs\Commands\Deploy('blocs:admin', 'Deploy blocs/admin package', __FILE__);
        });

        $this->commands('command.blocs.admin');

        $this->app->singleton('command.blocs.build', function ($app) {
            return new \Blocs\Commands\Build('blocs:build {path}', 'Build static contents');
        });

        $this->commands('command.blocs.build');

        $this->app->singleton('command.blocs.develop', function ($app) {
            return new \Blocs\Commands\Develop('blocs:develop {path}', 'Develop application');
        });

        $this->commands('command.blocs.develop');
    }

    public function registerPublish()
    {
        $publishList = [];

        // appをpublish
        $publishList[__DIR__.'/../app/Consts'] = app_path('Consts');
        $publishList[__DIR__.'/../app/Models'] = app_path('Models');
        $publishList[__DIR__.'/../app/Rules'] = app_path('Rules');

        // configをpublish
        $publishList[__DIR__.'/../config'] = config_path();

        // databaseをpublish
        $publishList[__DIR__.'/../database'] = database_path();

        // publicをpublish
        $publishList[__DIR__.'/../public'] = public_path();

        // resourceをpublish
        $publishList[__DIR__.'/../resources'] = resource_path();

        // routesをpublish
        $publishList[__DIR__.'/../routes'] = base_path('routes');

        $this->publishes($publishList);
    }
}
