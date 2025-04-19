<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;

class Agent extends Command
{
    use \Blocs\Agent\AgentTrait;

    protected $signature = 'blocs:agent';
    protected $description = 'Agent regression test';

    public function handle()
    {
        file_put_contents(resource_path('agent/latest.log'), '');

        do {
            $actions = ['test', 'add', 'clear', 'quit'];
            $stdin = $this->anticipate('アクション', array_reverse($actions));

            if (empty($stdin)) {
                // 入力なし
                continue;
            }

            if ($this->test($stdin)) {
                continue;
            }

            if ($this->add($stdin)) {
                continue;
            }

            if ($this->clear($stdin)) {
                continue;
            }

            $this->quit($stdin);
        } while (1);
    }

    private function test($stdin)
    {
        if ('test' !== strtolower($stdin)) {
            return false;
        }

        $testLogs = file_get_contents(resource_path('agent/test.log'));
        $testLogs = explode("\n", $testLogs);

        $lineNum = 0;
        $successNum = 0;
        foreach ($testLogs as $testLog) {
            ++$lineNum;
            $testLog = explode("\t", $testLog);
            if (5 !== count($testLog)) {
                continue;
            }

            $request = str_replace('{LF}', "\n", $testLog[0]);
            $state = str_replace('{LF}', "\n", $testLog[1]);
            $methods = explode(',', $testLog[3]);

            $chatMessage = $this->guessFunction($request, $state);
            $categories = implode(',', $this->categories);
            if (!$chatMessage->toolCalls
                || !in_array($chatMessage->toolCalls[0]->function->name, $methods)
                || $testLog[4] !== $chatMessage->toolCalls[0]->function->arguments) {
                $this->error("Line{$lineNum}: ".trim($request));
                dump($categories, $chatMessage);
            } else {
                $this->info("Line{$lineNum}");
                ++$successNum;
            }
        }
        $this->info($successNum.'ケース成功しました');

        return true;
    }

    private function add($stdin)
    {
        if ('add' !== strtolower($stdin)) {
            return false;
        }

        $latestLog = file_get_contents(resource_path('agent/latest.log'));
        if ($latestLog) {
            file_put_contents(resource_path('agent/test.log'), $latestLog, FILE_APPEND);
        }

        file_put_contents(resource_path('agent/latest.log'), '');

        return true;
    }

    private function clear($stdin)
    {
        if ('clear' !== strtolower($stdin)) {
            return false;
        }

        file_put_contents(resource_path('agent/latest.log'), '');

        return true;
    }

    private function quit($action)
    {
        // quit
        if ('quit' === strtolower($action) || 'exit' === strtolower($action) || 'bye' === strtolower($action)) {
            unlink(resource_path('agent/latest.log'));
            exit;
        }

        return false;
    }
}
