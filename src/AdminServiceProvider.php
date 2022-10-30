<?php

namespace Blocs;

use App\Console\Commands\BlocsAdmin;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerBlocsAdminCommand();
    }

    public function boot()
    {
    }

    public function registerBlocsAdminCommand()
    {
        $this->app->singleton('command.blocs.admin', function ($app) {
            return new BlocsAdmin();
        });

        $this->commands('command.blocs.admin');
    }
}
