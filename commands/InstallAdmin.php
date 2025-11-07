<?php

namespace Blocs\Commands;

use Illuminate\Support\Facades\Artisan;

class InstallAdmin extends Install
{
    /**
     * コンソールコマンドの署名。
     *
     * @var string
     */
    protected $signature = 'blocs:install';

    /**
     * コンソールコマンドの説明。
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
     * コマンド全体の実行フローを制御する。
     */
    public function handle(): void
    {
        parent::handle();

        // 空の favicon が残っていれば削除して不要ファイルを排除
        $faviconPath = public_path('favicon.ico');
        if (file_exists($faviconPath) && ! filesize($faviconPath)) {
            unlink($faviconPath);
        }

        // 必要ファイルを publish してアセットを公開
        Artisan::call('vendor:publish', ['--provider' => 'Blocs\AdminServiceProvider']);

        // 初期ユーザーを登録してすぐにログインできる状態を用意
        Artisan::call('migrate');
        Artisan::call('db:seed', ['--class' => 'AdminSeeder']);

        Artisan::call('route:cache');

        $this->info('Admin has been installed successfully.');
        $this->info('Login URL is '.route('login').'.');
        $this->info('Initial ID/Pass is admin/admin.');
    }
}
