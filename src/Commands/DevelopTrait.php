<?php

namespace Blocs\Commands;

use OpenAI\Laravel\Facades\OpenAI;

trait DevelopTrait
{
    private $item;
    private $actions;

    private function useOpenAi($developJson, $path)
    {
        $entryJson = [];
        $lastAction = '';

        $this->actions = [];
        $promptPath = base_path('docs/develop/prompt.txt');
        if (file_exists($promptPath)) {
            $prompts = file_get_contents($promptPath);
            $prompts = explode("\n", $prompts);

            foreach ($prompts as $prompt) {
                if (!strncmp($prompt, '#', 1)) {
                    continue;
                }
                if (empty(trim($prompt))) {
                    continue;
                }

                $this->actions[] = $prompt;
            }
        }

        do {
            if (empty($this->item)) {
                $actions = array_keys($developJson['entry']);
                array_unshift($actions, 'exit');
                $stdin = $this->anticipate('項目を入力', array_reverse($actions));

                if (empty($stdin) || $this->undoDevelop($entryJson, $developJson, $path, $stdin)) {
                    // 入力なしかundo
                    continue;
                }
                $this->exitDevelop($stdin);

                $this->item = str_replace(',', 'と', $stdin);
            }

            $actions = ['undo', 'exit', 'refresh', 'migrate'];
            empty($lastAction) || array_unshift($actions, $lastAction);
            $stdin = $this->anticipate($this->item.'項目のアクション', array_reverse($actions));

            if (empty($stdin)) {
                // 入力なし
                unset($this->item);
                continue;
            }
            if ($this->undoDevelop($entryJson, $developJson, $path, $stdin)) {
                // 入力なしかundo
                continue;
            }
            $this->exitDevelop($stdin);

            $actions = [];
            foreach ($this->actions as $action) {
                // 履歴から探索
                $action == $stdin || false === strpos($action, $stdin) || $actions[] = $action;
            }
            $this->actions[] = $stdin;

            if (!empty($actions)) {
                foreach ($actions as $action) {
                    $this->comment($action);
                }
                $stdin = $this->anticipate($this->item.'項目のアクション', array_reverse($actions));

                if (empty($stdin)) {
                    // 入力なし
                    unset($this->item);
                    continue;
                }
                if ($this->undoDevelop($entryJson, $developJson, $path, $stdin)) {
                    // 入力なしかundo
                    continue;
                }
                $this->exitDevelop($stdin);
            }

            $lastAction = $stdin;
            $this->tryDevelop($entryJson, $developJson, $path, $this->item.$stdin);
        } while (1);
    }

    private function updateJson($developJson, $path)
    {
        foreach (['controllerBasename', 'controllerDirname', 'modelBasename', 'modelDirname'] as $item) {
            unset($developJson['controller'][$item]);
        }

        file_put_contents($path, json_encode($developJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n") && chmod($path, 0666);

        $this->makeView($developJson, true);
    }

    private function exitDevelop($stdin)
    {
        if ('exit' !== strtolower($stdin) && 'bye' !== strtolower($stdin)) {
            return;
        }

        // 終了
        exit;
    }

    private function undoDevelop(&$entryJson, &$developJson, $path, $stdin)
    {
        if ('undo' !== strtolower($stdin)) {
            if ('refresh' === strtolower($stdin)) {
                $developJson = $this->refreshDevelopJson($path);
                $this->makeView($developJson, true);

                return true;
            }

            if ('migrate' === strtolower($stdin)) {
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

            return false;
        }

        // 戻す
        if ($newEntry = array_pop($entryJson)) {
            $developJson['entry'] = $newEntry;

            $this->updateJson($developJson, $path);
        } else {
            $this->error('undoできません');
        }

        return true;
    }

    private function askOpenAi($path, $stdin)
    {
        $this->info($stdin);

        // クエリを作成
        $content = file_get_contents($path).'に、'.$stdin.'、JSON形式で出力';

        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
        ]);

        $result = $result->choices[0]->message->content;
        $result = str_replace('```json', '', $result);
        $result = str_replace('```', '', $result);
        $result = trim(str_replace('```', '', $result));
        if (!json_validate($result)) {
            $this->error('うまくできませんでした');

            return false;
        }

        $newJson = json_decode($result, true);
        if (empty($newJson['entry'])) {
            $this->error('うまくできませんでした');

            return false;
        }

        return $newJson;
    }

    private function tryDevelop(&$entryJson, &$developJson, $path, $stdin)
    {
        if (!($newJson = $this->askOpenAi($path, $stdin))) {
            // 問い合わせ失敗
            return;
        }

        $entryJson[] = $developJson['entry'];
        $developJson['entry'] = $newJson['entry'];

        $this->updateJson($developJson, $path);
    }
}
