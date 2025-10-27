<?php

namespace Blocs\Commands;

class InstallAdmin extends Install
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocs:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install blocs/admin package';

    public function __construct()
    {
        $this->baseDir = base_path('vendor/blocs/admin');

        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        parent::handle();

        // 空のfaviconがあれば削除
        $faviconPath = public_path('favicon.ico');
        file_exists($faviconPath) && ! filesize($faviconPath) && unlink($faviconPath);

        // 必要ファイルをpublish
        \Artisan::call('vendor:publish', ['--provider' => 'Blocs\AdminServiceProvider']);

        // 初期ユーザー登録
        \Artisan::call('migrate');
        \Artisan::call('db:seed', ['--class' => 'AdminSeeder']);

        \Artisan::call('route:cache');

        echo "Admin has been installed successfully.\n";
        echo 'Login URL is '.route('login').".\n";
        echo "Initial ID/Pass is admin/admin.\n";
    }
}
