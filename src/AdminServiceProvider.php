<?php

namespace Blocs;

use Illuminate\Support\ServiceProvider;

class BlocsAdmin extends \App\Admin\Commands\Blocs
{
    public function handle()
    {
        /* 共通処理 */

        parent::handle();

        /* 初期ユーザー登録 */

        \Artisan::call('migrate');
        \Artisan::call('db:seed --class AdminSeeder');
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
        is_file(app_path('Consts/Admin.php')) && \App\Consts\Admin::define();

        // 言語設定を書き換え
        defined('BLOCS_LOCALE') && config(['app.locale' => BLOCS_LOCALE]);

        // ルーティング追加
        is_file(base_path('routes/admin.php')) && $this->loadRoutesFrom(base_path('routes/admin.php'));

        // configをpublish
        foreach (['menu.php', 'role.php'] as $configFile) {
            $this->publishes([
                __DIR__.'/../config/'.$configFile => config_path($configFile),
            ]);
        }
    }

    public function registerBlocsCommand()
    {
        $this->app->singleton('command.blocs.admin', function ($app) {
            return new BlocsAdmin('blocs:admin', 'Deploy blocs/admin package', __FILE__);
        });

        $this->commands('command.blocs.admin');
    }
}
