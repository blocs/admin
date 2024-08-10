<?php

namespace Blocs\Commands;

use OpenAI\Laravel\Facades\OpenAI;

trait DevelopTrait
{
    private $item;
    private $actions;
    private $prompt;

    private function useOpenAi($developJson, $path)
    {
        $entryJson = [];

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
                // 対象項目の入力
                echo 'Item > ';
                $stdin = trim(fgets(STDIN));

                if (empty($stdin) || $this->undoDevelop($entryJson, $developJson, $path, $stdin)) {
                    // 入力なしかundo
                    continue;
                }
                $this->exitDevelop($stdin);

                if ('re' === strtolower($stdin)) {
                    empty($this->prompt) || $this->tryDevelop($entryJson, $developJson, $path, $this->prompt);
                    continue;
                }
                $this->item = str_replace(',', 'と', $stdin);
            }

            echo $this->item.' > ';
            $stdin = trim(fgets(STDIN));

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

            if ('re' === strtolower($stdin)) {
                empty($this->prompt) || $this->tryDevelop($entryJson, $developJson, $path, $this->prompt);
                continue;
            }

            $actions = [];
            foreach ($this->actions as $action) {
                // 履歴から探索
                $action == $stdin || false === strpos($action, $stdin) || $actions[] = $action;
            }
            $this->actions[] = $stdin;

            if (!empty($actions)) {
                foreach ($actions as $action) {
                    echo '- '.$action."\n";
                }

                echo $this->item.' > ';
                $stdin = trim(fgets(STDIN));

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

                if ('re' === strtolower($stdin)) {
                    empty($this->prompt) || $this->tryDevelop($entryJson, $developJson, $path, $this->prompt);
                    continue;
                }
            }

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
                $developJson = json_decode(file_get_contents($path), true);
                $this->makeView($developJson, true);

                return true;
            }

            return false;
        }

        // 戻す
        if ($newEntry = array_pop($entryJson)) {
            $developJson['entry'] = $newEntry;

            $this->updateJson($developJson, $path);
        } else {
            echo "Can not undo\n";
        }

        return true;
    }

    private function askOpenAi($path, $stdin)
    {
        $this->prompt = $stdin;
        echo $stdin."\n";

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
            echo "Can not update\n";

            return false;
        }

        $newJson = json_decode($result, true);
        if (empty($newJson['entry'])) {
            echo "Can not update\n";

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
