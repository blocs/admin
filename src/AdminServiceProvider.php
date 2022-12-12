<?php

namespace Blocs;

use Illuminate\Support\ServiceProvider;

class BlocsAdmin extends \App\Console\Commands\Blocs
{
    public function handle()
    {
        /* ルーティング設定 */

        $blocsRoutesLoc = $this->stubDir.'/../routes/web.php';
        $laravelRoutesLoc = $this->rootDir.'/routes/web.php';
        if (is_file($blocsRoutesLoc) && is_file($laravelRoutesLoc)) {
            $laravelRoutes = file_get_contents($laravelRoutesLoc);
            if (false === strpos($laravelRoutes, 'Auth::routes();')) {
                // ルーティングを追加
                $blocsRoutes = file_get_contents($blocsRoutesLoc);
                file_put_contents($laravelRoutesLoc, "\n".$blocsRoutes, FILE_APPEND);
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
