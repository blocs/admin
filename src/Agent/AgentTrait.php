<?php

namespace Blocs\Agent;

trait AgentTrait
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

    private function guessTool()
    {
        request()->input('request') || $this->getSessionRequest();
        if (!request()->input('request')) {
            $this->initMessage();

            return;
        }

        if (request()->input('secret')) {
            // 生成AIに渡したくない情報は、セッションに保存
            $request = $this->putSecret(request()->input('request'));
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
            $state = request()->input('state');
            $chatMessage = $this->guessFunction($request, $state);

            // ログを出力
            if ($chatMessage->toolCalls && file_exists(resource_path($this->agent.'/latest.log'))) {
                $log = implode("\t", [
                    str_replace("\n", '{LF}', $request),
                    str_replace("\n", '{LF}', $state),
                    implode(',', $this->indexes),
                    $chatMessage->toolCalls[0]->function->name,
                    $chatMessage->toolCalls[0]->function->arguments,
                ]);

                file_put_contents(resource_path($this->agent.'/latest.log'), $log."\n", FILE_APPEND);
            }
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

    private function putSecret($value): string
    {
        // 10桁のランダムな文字列を生成
        $randomString = bin2hex(random_bytes(5));
        session([$randomString => $value]);

        return $randomString;
    }

    private function getSecret($value): string
    {
        if ($sessionValue = session($value)) {
            session()->forget($value);

            return $sessionValue;
        }

        return $value;
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

            if (!empty($redirect['login']) && true !== ($checkLogin = $this->checkLogin())) {
                return $checkLogin;
            }

            if (!empty($redirect['role']) && true !== ($checkRole = $this->checkRole($redirect['role']))) {
                return $checkRole;
            }
        }

        if (!method_exists($this, $method)) {
            if ('askText' === substr($method, 0, 7)) {
                return $this->askText(substr($method, 7));
            }

            if ('askSelect' === substr($method, 0, 9)) {
                return $this->askOption(substr($method, 3), 'select');
            }

            if ('askRadio' === substr($method, 0, 8)) {
                return $this->askOption(substr($method, 3), 'radio');
            }

            if ('redirect' === substr($method, 0, 8)) {
                return $this->redirect($redirect);
            }

            return 'お問い合わせをお願いします';
        }

        $arguments = json_decode($chatToolFunction->arguments, true);

        return call_user_func_array([$this, $method], $arguments);
    }

    private function askOption($name, $type)
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
}
