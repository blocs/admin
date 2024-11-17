<?php

namespace Blocs\Commands;

trait DevelopTrait
{
    private function refreshView($path)
    {
        do {
            $actions = ['refresh', 'migrate', 'exit'];
            $stdin = $this->anticipate('アクション', array_reverse($actions));

            if (empty($stdin)) {
                // 入力なし
                continue;
            }
            if ($this->refresh($path, $stdin)) {
                continue;
            }
            if ($this->migrate($path, $stdin)) {
                continue;
            }
            $this->exit($stdin);
        } while (1);
    }

    private function refresh($path, $stdin)
    {
        if ('refresh' !== strtolower($stdin)) {
            return false;
        }

        $developJson = $this->refreshDevelopJson($path);
        $this->makeView($developJson, true);

        return true;
    }

    private function migrate($path, $stdin)
    {
        if ('migrate' !== strtolower($stdin)) {
            return false;
        }

        $developJson = $this->refreshDevelopJson($path);

        $loopItem = $developJson['controller']['loopItem'];
        $migrationPath = 'create_'.$loopItem.'_table.php';

        if ($migrations = glob(database_path('migrations/*_'.$migrationPath))) {
            $migrationPath = $migrations[0];
            if ($this->confirm('Migrate "'.basename($migrationPath).'" ?')) {
                unlink($migrationPath);

                // テーブル再作成
                $this->makeMigration($developJson);

                $modelName = $developJson['controller']['modelName'];
                $modelPath = app_path("Models/{$modelName}.php");
                unlink($modelPath);

                // モデル再作成
                $this->makeModel($developJson);
            }
        }

        return true;
    }

    private function exit($stdin)
    {
        if ('exit' !== strtolower($stdin) && 'bye' !== strtolower($stdin)) {
            return false;
        }

        // 終了
        exit;
    }
}
