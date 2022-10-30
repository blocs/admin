<?php

namespace Blocs;

class AdminServiceProvider
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
            return new App\Console\Commands\BlocsAdmin();
        });

        $this->commands('command.blocs.admin');
    }
}
