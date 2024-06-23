<?php

namespace App\Console\Commands;

class DeployAdmin extends \Blocs\Commands\Deploy
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocs:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy blocs/admin package';

    /**
     * Execute the console command.
     */
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

        echo "Deploy was completed successfully.\n";

        \Artisan::call('route:cache');
        echo 'Login URL is '.route('login').".\n";
        echo "Initial ID/Pass is admin/admin.\n";
        \Artisan::call('route:clear');
    }
}
