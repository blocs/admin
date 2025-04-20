<?php

namespace Blocs\Commands;

use Illuminate\Console\Command;

class Agent extends Command
{
    use \Blocs\Agent\CommonTrait;

    protected $signature = 'blocs:agent {agent?}';
    protected $description = 'Agent regression test';

    private $errorLineNum = [];

    public function handle()
    {
        $this->agent = $this->argument('agent') ?? 'agent';

        file_put_contents(resource_path($this->agent.'/latest.log'), '');

        do {
            $actions = ['test', 'add'];
            count($this->errorLineNum) && $actions[] = 'retest('.count($this->errorLineNum).')';
            $actions = array_merge($actions, ['clear', 'quit']);

            $stdin = $this->anticipate('アクション', array_reverse($actions));

            if (empty($stdin)) {
                // 入力なし
                continue;
            }

            if ($this->test($stdin)) {
                continue;
            }

            if ('retest' === substr(strtolower($stdin), 0, 6)) {
                $this->test('test', $this->errorLineNum);
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

    private function test($stdin, $errorLineNum = null)
    {
        if ('test' !== strtolower($stdin)) {
            return false;
        }

        $testLogs = file_get_contents(resource_path($this->agent.'/test.log'));
        $testLogs = explode("\n", $testLogs);

        $this->errorLineNum = [];
        if (isset($errorLineNum)) {
            // retest
            $totalNum = count($errorLineNum);
        } else {
            $totalNum = $this->getTotalNum($testLogs);
        }
        $progressBar = $this->output->createProgressBar($totalNum);

        // 開始
        $progressBar->start();

        $lineNum = 0;
        $successNum = 0;
        $errors = [];
        foreach ($testLogs as $testLog) {
            ++$lineNum;
            $testLog = explode("\t", $testLog);
            if (5 !== count($testLog)) {
                continue;
            }

            if (isset($errorLineNum) && !in_array($lineNum, $errorLineNum)) {
                // retestではskip
                continue;
            }

            // プログレスバーを進める
            $progressBar->advance();

            $request = str_replace('{LF}', "\n", $testLog[0]);
            $state = str_replace('{LF}', "\n", $testLog[1]);
            $methods = explode(',', $testLog[3]);
            $arguments = str_replace(' ', '', $testLog[4]);

            $chatMessage = $this->guessFunction($request, $state);
            $indexes = implode(',', $this->indexes);
            if (!$chatMessage->toolCalls
                || !in_array($chatMessage->toolCalls[0]->function->name, $methods)
                || $arguments !== str_replace(' ', '', $chatMessage->toolCalls[0]->function->arguments)) {
                $errors[] = [
                    'lineNum' => $lineNum,
                    'request' => $request,
                    'indexes' => $indexes,
                    'testLog' => $testLog,
                    'chatMessage' => $chatMessage,
                ];

                $this->errorLineNum[] = $lineNum;
            } else {
                ++$successNum;
            }
        }

        // 終了
        $progressBar->finish();
        $this->info("\n".$successNum.'ケース成功しました');

        foreach ($errors as $error) {
            $this->error("\n".$this->echoRequest($error['lineNum'], $error['request']));
            dump($error['indexes'], $error['testLog'], $error['chatMessage']);
        }

        return true;
    }

    private function getTotalNum($testLogs)
    {
        $totalNum = 0;
        foreach ($testLogs as $testLog) {
            $testLog = explode("\t", $testLog);
            5 === count($testLog) && ++$totalNum;
        }

        return $totalNum;
    }

    private function echoRequest($lineNum, $request)
    {
        $request = str_replace(["\r\n", "\r", "\n"], ' ', $request);

        return $lineNum.': '.mb_substr(trim($request), 0, 50);
    }

    private function add($stdin)
    {
        if ('add' !== strtolower($stdin)) {
            return false;
        }

        $latestLog = file_get_contents(resource_path($this->agent.'/latest.log'));
        if ($latestLog) {
            file_put_contents(resource_path($this->agent.'/test.log'), $latestLog, FILE_APPEND);
        }

        file_put_contents(resource_path($this->agent.'/latest.log'), '');

        return true;
    }

    private function clear($stdin)
    {
        if ('clear' !== strtolower($stdin)) {
            return false;
        }

        file_put_contents(resource_path($this->agent.'/latest.log'), '');

        return true;
    }

    private function quit($action)
    {
        if ('quit' === strtolower($action) || 'exit' === strtolower($action) || 'bye' === strtolower($action)) {
            unlink(resource_path($this->agent.'/latest.log'));
            exit;
        }

        return false;
    }
}
