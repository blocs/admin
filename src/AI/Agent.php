<?php

namespace Blocs\AI;

use OpenAI\Laravel\Facades\OpenAI;

class Agent
{
    private $collectionName;

    private $model;

    private $developer;

    private bool $isLogging;

    public function __construct($collectionName = null, $model = 'gpt-5-chat-latest')
    {
        $this->collectionName = $collectionName;
        $this->model = $model;
    }

    public function developer($developer = null)
    {
        if (isset($developer)) {
            $this->developer = $developer;
        } else {
            unset($this->developer);
        }

        return $this;
    }

    public function question()
    {
        $messages = [
            [
                'role' => 'developer',
                'content' => file_get_contents(resource_path('prompt/question.md')),
            ],
        ];

        $allIds = VectorStore::getAllIds($this->collectionName);
        $knowledge = VectorStore::get($this->collectionName, $allIds[array_rand($allIds)])['content'];

        $userContent = [];
        $userContent[] = [
            'type' => 'text',
            'text' => "# ナレッジ\n".$knowledge,
        ];

        $messages[] = [
            'role' => 'user',
            'content' => $userContent,
        ];

        $chatOpenAI = [
            'model' => $this->model,
            'messages' => $messages,
        ];
        empty($this->isLogging) || $this->storeLog($chatOpenAI);

        $result = OpenAI::chat()->create($chatOpenAI);

        return trim($result->choices[0]->message->content);
    }

    public function translate($question, $answer, $questionLang = null)
    {
        if (empty($questionLang)) {
            $language = $this->detectLanguage($question, $answer);
            [$questionLang, $answerLang] = explode("\n", $language);

            if (trim($questionLang) == trim($answerLang)) {
                // 翻訳は不要
                return $answer;
            }
        }

        $developer = file_get_contents(resource_path('prompt/translate.md'));
        $developer = str_replace('{{language}}', trim($questionLang), $developer);

        $messages = [
            [
                'role' => 'developer',
                'content' => $developer,
            ],
        ];

        $userContent = [];
        $userContent[] = [
            'type' => 'text',
            'text' => "# 翻訳したい文章\n".$answer,
        ];

        $messages[] = [
            'role' => 'user',
            'content' => $userContent,
        ];

        $chatOpenAI = [
            'model' => $this->model,
            'messages' => $messages,
        ];
        empty($this->isLogging) || $this->storeLog($chatOpenAI);

        $result = OpenAI::chat()->create($chatOpenAI);

        return trim($result->choices[0]->message->content);
    }

    public static function checkLocale(): ?string
    {
        $acceptLanguage = request()->header('Accept-Language');
        if (empty($acceptLanguage)) {
            return app()->getLocale();
        }

        $languages = explode(',', $acceptLanguage);
        foreach ($languages as $language) {
            $langCode = explode('-', trim($language))[0];

            return $langCode;
        }

        return app()->getLocale();
    }

    public function log($isLogging)
    {
        $this->isLogging = $isLogging;

        return $this;
    }

    private function detectLanguage($question, $answer)
    {
        $messages = [
            [
                'role' => 'developer',
                'content' => file_get_contents(resource_path('prompt/detectLanguage.md')),
            ],
        ];

        $userContent = [];
        $userContent[] = [
            'type' => 'text',
            'text' => "# 言語を特定するための文章\n".$question,
        ];
        $userContent[] = [
            'type' => 'text',
            'text' => "# 翻訳したい文章\n".$answer,
        ];

        $messages[] = [
            'role' => 'user',
            'content' => $userContent,
        ];

        $chatOpenAI = [
            'model' => $this->model,
            'messages' => $messages,
        ];
        empty($this->isLogging) || $this->storeLog($chatOpenAI);

        $result = OpenAI::chat()->create($chatOpenAI);

        return trim($result->choices[0]->message->content);
    }

    private function storeLog($chatOpenAI)
    {
        file_put_contents(storage_path('logs/chatOpenAI.log'), json_encode($chatOpenAI, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
    }
}
