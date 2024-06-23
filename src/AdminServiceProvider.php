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
        $this->app->singleton('command.blocs.develop', function ($app) {
            return new Commands\Develop('blocs:develop {path}', 'Develop application');
        });

        $this->commands('command.blocs.develop');
    }

    public function registerPublish()
    {
        $publishList = [];

        // appをpublish
        $publishList[__DIR__.'/../app'] = app_path();

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
