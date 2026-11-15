<?php

namespace Blocs;

use App\Consts\Blocs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;

class AdminServiceProvider extends ServiceProvider
{
    public function register()
    {
        // コンソールコマンドの登録処理を実行
        $this->app->runningInConsole() && $this->registerBlocsCommand();
    }

    public function boot()
    {
        // アプリケーション定数の読み込みを実行
        is_file(app_path('Consts/Blocs.php')) && Blocs::define();

        // 管理画面用のルーティングを追加
        is_file(base_path('routes/admin.php')) && $this->loadRoutesFrom(base_path('routes/admin.php'));

        // Artisan publish 対象のファイルを登録
        $this->app->runningInConsole() && $this->registerPublish();

        // 常駐ワーカー（Octane）でリクエストごとにメニューの静的な状態を初期化
        $this->registerMenuStateFlush();

        // 削除・無効化されたユーザーを認証対象から除外
        is_file(base_path('routes/admin.php')) && $this->excludeInactiveUsersFromAuthentication();
    }

    /**
     * 認証に使うモデルに、削除・無効化されたユーザーを除外するスコープを追加する。
     * ログイン済みのセッションも、削除・無効化された時点で認証済みではなくなる。
     */
    private function excludeInactiveUsersFromAuthentication(): void
    {
        $authModel = $this->resolveAuthModel();
        if ($authModel === null) {
            return;
        }

        $authModel::addGlobalScope('blocsActiveUser', function (Builder $builder): void {
            $builder
                ->whereNull($builder->qualifyColumn('deleted_at'))
                ->whereNull($builder->qualifyColumn('disabled_at'));
        });
    }

    /**
     * 既定のガードが使う Eloquent モデルのクラス名を返す。
     * ユーザー管理画面のモデルは無効化されたユーザーも扱うため、対象にしない。
     *
     * @return class-string<Model>|null
     */
    private function resolveAuthModel(): ?string
    {
        $provider = config('auth.guards.'.config('auth.defaults.guard').'.provider');
        $authModel = config('auth.providers.'.$provider.'.model');

        if (! is_string($authModel) || ! is_subclass_of($authModel, Model::class)) {
            return null;
        }

        return is_a($authModel, 'App\\Models\\Admin\\User', true) ? null : $authModel;
    }

    /**
     * 常駐ワーカー（Laravel Octane）ではリクエストをまたいで static が残るため、
     * リクエスト開始時に見出しとパンくずリストを初期化する。Octane 未導入なら何もしない。
     */
    private function registerMenuStateFlush(): void
    {
        if (! class_exists(RequestReceived::class)) {
            return;
        }

        $this->app['events']->listen(RequestReceived::class, function (): void {
            Menu::flush();
        });
    }

    public function registerBlocsCommand()
    {
        $this->app->singleton('command.blocs.install', fn () => new Commands\Install);

        $this->commands('command.blocs.install');
    }

    /**
     * パッケージのファイルを、アプリの対応するディレクトリへ publish できるよう登録する。
     */
    public function registerPublish()
    {
        $packagePath = dirname(__DIR__);

        $this->publishes([
            $packagePath.'/app' => app_path(),
            $packagePath.'/config' => config_path(),
            $packagePath.'/database' => database_path(),
            $packagePath.'/public' => public_path(),
            $packagePath.'/resources' => resource_path(),
            $packagePath.'/routes' => base_path('routes'),
        ]);
    }
}
