<?php

namespace Blocs;

use Illuminate\Support\ServiceProvider;

class BlocsAdmin extends \Blocs\Commands\Deploy
{
    public function handle()
    {
        parent::handle();

        // 空のfaviconがあれば削除
        $faviconPath = public_path('favicon.ico');
        file_exists($faviconPath) && !filesize($faviconPath) && unlink($faviconPath);

        // 必要ファイルをpublish
        \Artisan::call('vendor:publish', ['--provider' => 'Blocs\AdminServiceProvider']);

        // 初期ユーザー登録
        \Artisan::call('migrate');
        \Artisan::call('db:seed', ['--class' => 'AdminSeeder']);
        \Artisan::call('route:cache');

        echo "Deploy was completed successfully.\n";
        echo 'Login URL is '.route('login').".\n";
        echo "Initial ID/Pass is admin/admin.\n";
    }
}

class AdminServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerBlocsCommand();
    }

    public function boot()
    {
        // 定数の読み込み
        is_file(app_path('Consts/Blocs.php')) && \App\Consts\Blocs::define();

        // 言語設定を書き換え
        defined('BLOCS_LOCALE') && config(['app.locale' => BLOCS_LOCALE]);

        // ルーティング追加
        is_file(base_path('routes/admin.php')) && $this->loadRoutesFrom(base_path('routes/admin.php'));

        $publishList = [];

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

    public function registerBlocsCommand()
    {
        $this->app->singleton('command.blocs.admin', function ($app) {
            return new BlocsAdmin('blocs:admin', 'Deploy blocs/admin package', __FILE__);
        });

        $this->commands('command.blocs.admin');

        $this->app->singleton('command.blocs.build', function ($app) {
            return new \Blocs\Commands\Build('blocs:build {path?}', 'Build static contents');
        });

        $this->commands('command.blocs.build');
    }
}
