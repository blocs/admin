<?php

namespace App\Http\Controllers\Admin;

use OpenAI\Laravel\Facades\OpenAI;

trait OperatorTrait
{
    private function getError(): bool
    {
        if (!session('errors')) {
            return false;
        }

        $messages = session('errors')->getBag('default')->getMessages();
        $messages = array_shift($messages);

        $this->val = [
            'message' => array_shift($messages),
        ];

        // old()の値をクリア
        session()->flash('_old_input', []);

        return true;
    }

    private function findTool()
    {
        request()->input('request') || $this->getSessionRequest();
        if (!request()->input('request')) {
            if (\Auth::user()) {
                $defaultMessage = \Auth::user()->name.'さん、何かリクエストを入力してください。';
            } else {
                $defaultMessage = '何かリクエストを入力してください。';
            }

            $this->val = array_merge($this->val, [
                'message' => $defaultMessage,
            ]);

            return;
        }

        if (request()->input('secret')) {
            // 生成AIに渡したくない情報は、セッションに保存
            $request = $this->generateSecret(request()->input('request'));
            request()->merge([
                'request' => $request,
            ]);
        }

        if (request()->input('template')) {
            // テンプレートがある場合は、リクエストをテンプレートに置き換える
            $request = str_replace('{request}', request()->input('request'), request()->input('template'));
            request()->merge([
                'request' => $request,
            ]);

            $this->setRequests(request()->input('request')."\n".request()->input('requests'));
        } else {
            $this->setRequests(request()->input('requests')."\n".request()->input('request'));
        }

        $request = $this->val['requests'];

        foreach ([resource_path('operator/functions.json'), resource_path('operator/arguments.json')] as $toolsFile) {
            $chatMessage = $this->findFunction($toolsFile, $request);
            if ($chatMessage->content) {
                $request = $chatMessage->content;
                continue;
            }

            $response = $this->execFunction($chatMessage->toolCalls[0]->function);
            if (is_object($response)) {
                return $response;
            }

            if (is_array($response)) {
                $this->val = $response;

                return;
            }

            $request = $response;
        }

        $this->setRequests(request()->input('requests')."\n".$request);
        $this->val['message'] = $request;
    }

    private function generateSecret($value)
    {
        // 10桁のランダムな文字列を生成
        $randomString = bin2hex(random_bytes(5));
        session()->flash($randomString, $value);

        return $randomString;
    }

    private function setRequests($requests)
    {
        request()->merge([
            'requests' => $requests,
        ]);
        $this->val['requests'] = $requests;
    }

    private function execFunction($chatToolFunction)
    {
        $method = $chatToolFunction->name;
        $arguments = json_decode($chatToolFunction->arguments, true);
        request()->merge($arguments);
        $arguments = [request()];

        return call_user_func_array([$this, $method], $arguments);
    }

    private function getSessionRequest()
    {
        // リクエストがない場合は、セッションから取得
        if (!session()->has('requests')) {
            return;
        }

        // セッションからリクエストを取得
        $requests = session('requests');
        $request = array_shift($requests);
        request()->merge([
            'request' => $request,
        ]);

        // セッションに保存
        if ($requests) {
            session(['requests' => $requests]);
        } else {
            session()->forget('requests');
        }
    }

    private function getTools($jsonFile)
    {
        $functions = json_decode(file_get_contents($jsonFile), true);

        $tools = array_map(function ($function) {
            // propertiesをobjectに変換
            $function['parameters']['properties'] = (object) $function['parameters']['properties'];

            $tool = [
                'type' => 'function',
                'function' => $function,
            ];

            return $tool;
        }, $functions);

        return $tools;
    }

    private function findFunction($jsonFile, $request)
    {
        $userContent = [];
        if (request()->input('title')) {
            $userContent[] = [
                'type' => 'text',
                'text' => '表示している画面: '.request()->input('title'),
            ];
        }
        $userContent[] = [
            'type' => 'text',
            'text' => 'リクエスト: '.trim($request),
        ];

        $message = [
            [
                'role' => 'system',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => '与えられたリクエストに従って、計算処理を呼び出してください。',
                    ],
                    [
                        'type' => 'text',
                        'text' => '計算処理に必要なデータがあれば、簡潔に入力を指示してください。',
                    ],
                ],
            ],
            [
                'role' => 'assistant',
                'content' => file_get_contents(resource_path('operator/assistant.md')),
            ],
            [
                'role' => 'user',
                'content' => $userContent,
            ],
        ];

        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $message,
            'tools' => $this->getTools($jsonFile),
            'temperature' => 0,
        ]);

        return $result->choices[0]->message;
    }
}
