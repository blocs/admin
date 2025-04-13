<?php

namespace Blocs\Agent;

use OpenAI\Laravel\Facades\OpenAI;

trait AgentTrait
{
    private $categories;

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

    private function guessTool()
    {
        request()->input('request') || $this->getSessionRequest();
        if (!request()->input('request')) {
            $this->initMessage();

            return;
        }

        if (request()->input('secret')) {
            // 生成AIに渡したくない情報は、セッションに保存
            $request = $this->generateSecret(request()->input('request'));
            request()->merge([
                'request' => $request,
            ]);
        }

        if (request()->input('template') && request()->input('request')) {
            // テンプレートがある場合は、リクエストをテンプレートに置き換える
            $request = str_replace('{request}', request()->input('request'), request()->input('template'));
            request()->merge([
                'request' => $request,
            ]);

            $this->setRequests(request()->input('request')."\n".request()->input('requests'));
        } else {
            $this->setRequests(request()->input('requests')."\n".request()->input('request'));
        }

        if (request()->input('function') && request()->input('request')) {
            // メソッドを指定して実行
            $method = str_replace('{request}', request()->input('request'), request()->input('function'));

            $arguments = [];
            request()->input('argument') && $arguments[request()->input('argument')] = request()->input('request');

            $chatMessage = (object) [];
            $chatMessage->toolCalls = [
                (object) [
                    'function' => (object) [
                        'name' => $method,
                        'arguments' => json_encode($arguments),
                    ],
                ],
            ];
        } else {
            $request = $this->val['requests'];
            $chatMessage = $this->guessFunction($request);
        }

        if ($chatMessage->toolCalls) {
            $response = $this->execFunction($chatMessage->toolCalls[0]->function);
            if (is_object($response)) {
                return $response;
            }

            if (is_array($response)) {
                $this->val = $response;

                return;
            }

            $request = $response;
        } else {
            $request = $chatMessage->content;
        }

        $this->setRequests(request()->input('requests')."\n".$request);
        $this->val['message'] = $request;
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

    private function generateSecret($value): string
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

        if ('redirect' === substr($method, 0, 8)) {
            $redirects = $this->getJsonAll('redirect');
            foreach ($redirects as $redirect) {
                if (substr($method, 8) === $redirect['name']) {
                    break;
                }
            }

            if (!empty($redirect['login']) && true !== ($responseLogin = $this->checkLogin())) {
                return $responseLogin;
            }

            if (!empty($redirect['role']) && true !== ($responseRole = $this->checkRole($redirect['role']))) {
                return $responseRole;
            }
        }

        if (!method_exists($this, $method)) {
            if ('askText' === substr($method, 0, 7)) {
                return $this->askText(substr($method, 7));
            }

            if ('askSelect' === substr($method, 0, 9)) {
                return $this->responseAskOption(substr($method, 3), 'select');
            }

            if ('askRadio' === substr($method, 0, 8)) {
                return $this->responseAskOption(substr($method, 3), 'radio');
            }

            if ('redirect' === substr($method, 0, 8)) {
                return $this->redirect($redirect);
            }

            return 'お問い合わせをお願いします';
        }

        $arguments = json_decode($chatToolFunction->arguments, true);

        return call_user_func_array([$this, $method], $arguments);
    }

    private function responseAskOption($name, $type)
    {
        $asks = $this->getJsonAll('ask');
        foreach ($asks as $ask) {
            if ($name === $ask['name']) {
                break;
            }
        }

        $options = [];
        foreach ($ask['options'] as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        $this->val = array_merge($this->val, [
            $type => true,
            'function' => $ask['function'],
            'options' => $options,
        ]);
        isset($ask['message']) && $this->val['message'] = $ask['message'];

        return view($this->viewPrefix.'.agent', $this->val);
    }

    private function askText($name)
    {
        $asks = $this->getJsonAll('ask');
        foreach ($asks as $ask) {
            if ('Text'.$name === $ask['name']) {
                break;
            }
        }

        foreach (['function', 'message', 'type', 'template', 'argument', 'secret'] as $key) {
            isset($ask[$key]) && $this->val[$key] = $ask[$key];
        }

        return view($this->viewPrefix.'.agent', $this->val);
    }

    private function redirect($redirect)
    {
        if (isset($redirect['route'])) {
            if (isset($redirect['argument'])) {
                return redirect()->route($redirect['route'], $redirect['argument']);
            }

            return redirect()->route($redirect['route']);
        }

        return redirect()->to($redirect['url']);
    }

    private function getJsonAll($type)
    {
        $jsonData = $this->getJson(resource_path('agent/'.$type.'.json'));
        if ($jsonFiles = glob(resource_path('agent/*/'.$type.'.json'))) {
            foreach ($jsonFiles as $jsonFile) {
                $jsonData = array_merge($jsonData, $this->getJson($jsonFile));
            }
        }

        return $jsonData;
    }

    private function guessFunction($request)
    {
        $userContent = [];
        $userContent[] = [
            'type' => 'text',
            'text' => "# リクエスト\n".trim($request),
        ];
        if (request()->input('title')) {
            $userContent[] = [
                'type' => 'text',
                'text' => "# 現在の画面\n".request()->input('title'),
            ];
        }

        // ナレッジを取得
        $this->categories = $this->guessCategory($request);
        $assistant = '';
        foreach ($this->categories as $category) {
            $assistant .= $this->replaceWords(resource_path('agent/'.$category.'/assistant.md'));
        }
        $assistant .= "\n".$this->replaceWords(resource_path('agent/assistant.md'));

        $message = [
            [
                'role' => 'system',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => '現在の画面と与えられたリクエストにマッチする、計算処理を呼び出してください',
                    ],
                    [
                        'type' => 'text',
                        'text' => '呼び出す計算処理が特定できない時は、追加の質問をしてください',
                    ],
                    [
                        'type' => 'text',
                        'text' => '計算処理に必要なデータがあれば、簡潔に入力を指示してください',
                    ],
                ],
            ],
            [
                'role' => 'assistant',
                'content' => $assistant,
            ],
            [
                'role' => 'user',
                'content' => $userContent,
            ],
        ];

        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $message,
            'tools' => $this->getTools($request),
            'temperature' => 0,
        ]);

        return $result->choices[0]->message;
    }

    private function getTools($request)
    {
        $functions = [];

        // 入力処理を取得
        foreach ($this->categories as $category) {
            $functions = $this->addRedirects($functions, resource_path('agent/'.$category.'/ask.json'), 'ask');
        }
        $functions = $this->addRedirects($functions, resource_path('agent/ask.json'), 'ask');

        // リダイレクト処理を取得
        foreach ($this->categories as $category) {
            $functions = $this->addRedirects($functions, resource_path('agent/'.$category.'/redirect.json'), 'redirect');
        }
        $functions = $this->addRedirects($functions, resource_path('agent/redirect.json'), 'redirect');

        // 計算処理を取得
        foreach ($this->categories as $category) {
            $functions = array_merge($functions, $this->getJson(resource_path('agent/'.$category.'/function.json')));
        }
        $functions = array_merge($functions, $this->getJson(resource_path('agent/function.json')));

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

    private function addRedirects($functions, $jsonFile, $prefix)
    {
        $redirects = $this->getJson($jsonFile);

        foreach ($redirects as $redirect) {
            method_exists($this, $redirect['name']) || $redirect['name'] = $prefix.$redirect['name'];

            isset($redirect['description']) && $functions[] = [
                'name' => $redirect['name'],
                'description' => $redirect['description'],
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                ],
            ];
        }

        return $functions;
    }

    private function replaceWords($jsonFile): string
    {
        if (!file_exists($jsonFile)) {
            return '';
        }

        $jsonData = file_get_contents($jsonFile);
        if (config('openai.replace_words')) {
            foreach (config('openai.replace_words') as $key => $value) {
                $jsonData = str_replace('{'.$key.'}', $value, $jsonData);
            }
        }

        return $jsonData;
    }

    private function getJson($jsonFile): array
    {
        if (!file_exists($jsonFile)) {
            return [];
        }

        $jsonData = $this->replaceWords($jsonFile);
        strlen($jsonData) || $jsonData = '[]';

        return json_decode($jsonData, true);
    }

    private function guessCategory($request)
    {
        $categoryMd = resource_path('agent/category.md');
        if (!file_exists($categoryMd)) {
            return '';
        }

        $assistant = $this->replaceWords($categoryMd);
        $assistant .= "\n".$this->replaceWords(resource_path('agent/assistant.md'));

        $userContent = [];
        $userContent[] = [
            'type' => 'text',
            'text' => "# リクエスト\n".trim($request),
        ];
        if (request()->input('title')) {
            $userContent[] = [
                'type' => 'text',
                'text' => "# 現在の画面\n".request()->input('title'),
            ];
        }

        $message = [
            [
                'role' => 'system',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => '現在の画面と与えられたリクエストにマッチするカテゴリーを特定してください',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'カテゴリーが特定できた時は、英数字のカテゴリー名だけをタブ区切りで返してください',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'カテゴリーが特定できない時は、空白を返してください',
                    ],
                ],
            ],
            [
                'role' => 'assistant',
                'content' => $assistant,
            ],
            [
                'role' => 'user',
                'content' => $userContent,
            ],
        ];

        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $message,
            'temperature' => 0,
            'max_tokens' => 1,
        ]);

        return explode("\t", trim($result->choices[0]->message->content));
    }
}
