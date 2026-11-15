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
        $consoleCommandBindings = [
            'command.blocs.install' => Commands\Install::class,
        ];

        foreach ($consoleCommandBindings as $consoleCommandKey => $consoleCommandClass) {
            $this->registerConsoleCommand($consoleCommandKey, $consoleCommandClass);
        }
    }

    public function registerPublish()
    {
        $this->publishes($this->buildPublishMappings());
    }

    private function registerConsoleCommand(string $consoleCommandKey, string $consoleCommandClass): void
    {
        $this->app->singleton($consoleCommandKey, function () use ($consoleCommandClass) {
            return new $consoleCommandClass;
        });

        $this->commands($consoleCommandKey);
    }

    private function buildPublishMappings(): array
    {
        $publishMappings = [];

        // appをpublish
        $publishMappings[base_path('vendor/blocs/admin/app')] = app_path();

        // configをpublish
        $publishMappings[base_path('vendor/blocs/admin/config')] = config_path();

        // databaseをpublish
        $publishMappings[base_path('vendor/blocs/admin/database')] = database_path();

        // publicをpublish
        $publishMappings[base_path('vendor/blocs/admin/public')] = public_path();

        // resourceをpublish
        $publishMappings[base_path('vendor/blocs/admin/resources')] = resource_path();

        // routesをpublish
        $publishMappings[base_path('vendor/blocs/admin/routes')] = base_path('routes');

        return $publishMappings;
    }
}
