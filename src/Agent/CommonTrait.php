<?php

namespace Blocs\Agent;

use OpenAI\Laravel\Facades\OpenAI;

trait CommonTrait
{
    private $indexes;
    private $agent;

    private function guessFunction($request, $state = null)
    {
        $userContent = [];
        $stateMessage = '';
        $userContent[] = [
            'type' => 'text',
            'text' => "# リクエスト\n".trim($request),
        ];
        if ($state) {
            $userContent[] = [
                'type' => 'text',
                'text' => "# 状況\n".$state,
            ];
            $stateMessage = '状況で';
        }

        $systemContent = [];
        $system = $this->replaceWords(resource_path($this->agent.'system.md'));
        $system && $systemContent[] = [
            'type' => 'text',
            'text' => trim($system),
        ];
        $systemContent[] = [
            'type' => 'text',
            'text' => '与えられた'.$stateMessage.'リクエストにマッチする計算処理を呼び出してください',
        ];
        $systemContent[] = [
            'type' => 'text',
            'text' => '呼び出す計算処理が特定できない時は、追加質問をしてください',
        ];
        $systemContent[] = [
            'type' => 'text',
            'text' => '計算処理に必要なデータがあれば、簡潔に入力を指示してください',
        ];

        // ナレッジを取得
        $this->indexes = $this->guessIndex($userContent, $stateMessage);

        $assistantContent = [];
        foreach ($this->indexes as $index) {
            $assistantContent[] = [
                'type' => 'text',
                'text' => $this->replaceWords(resource_path($this->agent.'/'.$index.'/assistant.md')),
            ];
        }
        $assistant = $this->replaceWords(resource_path($this->agent.'/assistant.md'));
        $assistant && $assistantContent[] = [
            'type' => 'text',
            'text' => $assistant,
        ];

        $message = [
            [
                'role' => 'system',
                'content' => $systemContent,
            ],
            [
                'role' => 'assistant',
                'content' => $assistantContent,
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
            'top_p' => 0.0,
            'temperature' => 0.0,
        ]);

        return $result->choices[0]->message;
    }

    private function guessIndex($userContent, $stateMessage)
    {
        $indexMd = resource_path($this->agent.'/index.md');
        if (!file_exists($indexMd)) {
            return '';
        }

        $systemContent = [];
        $system = $this->replaceWords(resource_path($this->agent.'/system.md'));
        $system && $systemContent[] = [
            'type' => 'text',
            'text' => trim($system),
        ];
        $systemContent[] = [
            'type' => 'text',
            'text' => '与えられた'.$stateMessage.'リクエストにマッチするカテゴリーを特定してください',
        ];
        $systemContent[] = [
            'type' => 'text',
            'text' => 'カテゴリーを特定できた時は、英数字のカテゴリー名だけ返してください',
        ];
        $systemContent[] = [
            'type' => 'text',
            'text' => 'カテゴリーが複数ある時は、英数字のカテゴリー名だけをタブ区切りで返してください',
        ];
        $systemContent[] = [
            'type' => 'text',
            'text' => 'カテゴリーを特定できない時は、noneを返してください',
        ];

        $assistantContent = [];
        $assistantContent[] = [
            'type' => 'text',
            'text' => "# カテゴリー\n".$this->replaceWords($indexMd),
        ];

        $message = [
            [
                'role' => 'system',
                'content' => $systemContent,
            ],
            [
                'role' => 'assistant',
                'content' => $assistantContent,
            ],
            [
                'role' => 'user',
                'content' => $userContent,
            ],
        ];

        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $message,
            'top_p' => 0.0,
            'temperature' => 0.0,
            'max_tokens' => 5,
        ]);

        return explode("\t", trim($result->choices[0]->message->content));
    }

    private function getTools($request)
    {
        $functions = [];

        // 入力処理を取得
        foreach ($this->indexes as $index) {
            $functions = $this->addRedirects($functions, resource_path($this->agent.'/'.$index.'/ask.json'), 'ask');
        }
        $functions = $this->addRedirects($functions, resource_path($this->agent.'/ask.json'), 'ask');

        // リダイレクト処理を取得
        foreach ($this->indexes as $index) {
            $functions = $this->addRedirects($functions, resource_path($this->agent.'/'.$index.'/redirect.json'), 'redirect');
        }
        $functions = $this->addRedirects($functions, resource_path($this->agent.'/redirect.json'), 'redirect');

        // 計算処理を取得
        foreach ($this->indexes as $index) {
            $functions = array_merge($functions, $this->getJson(resource_path($this->agent.'/'.$index.'/function.json')));
        }
        $functions = array_merge($functions, $this->getJson(resource_path($this->agent.'/function.json')));

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

    private function getJsonAll($type)
    {
        $jsonData = $this->getJson(resource_path($this->agent.'/'.$type.'.json'));
        if ($jsonFiles = glob(resource_path($this->agent.'/*/'.$type.'.json'))) {
            foreach ($jsonFiles as $jsonFile) {
                $jsonData = array_merge($jsonData, $this->getJson($jsonFile));
            }
        }

        return $jsonData;
    }
}
