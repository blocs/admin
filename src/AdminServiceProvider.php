<?php

namespace Blocs;

use Illuminate\Support\ServiceProvider;

class BlocsAdmin extends \App\Console\Commands\Blocs
{
    public function handle()
    {
        /* ルーティング設定 */

        $blocs_routes_loc = self::$stub_dir.'/../routes/web.php';
        $laravel_routes_loc = self::$root_dir.'/routes/web.php';
        if (is_file($blocs_routes_loc) && is_file($laravel_routes_loc)) {
            $laravel_routes = file_get_contents($laravel_routes_loc);
            if (false === strpos($laravel_routes, 'Auth::routes();')) {
                // ルーティングを追加
                $blocs_routes = file_get_contents($blocs_routes_loc);
                file_put_contents($laravel_routes_loc, "\n".$blocs_routes, FILE_APPEND);
            }
        }

        parent::handle();
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
    }

    public function registerBlocsCommand()
    {
        $this->app->singleton('command.blocs.admin', function ($app) {
            return new BlocsAdmin('blocs:admin', 'Deploy blocs/admin package', __FILE__);
        });

        $this->commands('command.blocs.admin');
    }
}
