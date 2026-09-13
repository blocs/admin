<?php

namespace Blocs\AI;

use OpenAI\Laravel\Facades\OpenAI;

class Chat
{
    private $collectionName;

    private $model;

    private bool $isLogging;

    public function __construct($collectionName, $model)
    {
        $this->collectionName = $collectionName;
        $this->model = $model;
    }

    public function question($messages = [])
    {
        $samples = VectorStore::sample($this->collectionName, 1);
        $knowledge = $samples[0]['content'] ?? '';

        $developerContent = [];
        $developerContent[] = [
            'role' => 'developer',
            'content' => file_get_contents(resource_path('prompt/question.md')),
        ];
        $developerContent[] = [
            'role' => 'developer',
            'content' => "# ナレッジ\n".$knowledge,
        ];

        $messages = array_merge($developerContent, $messages);

        $chatOpenAI = [
            'model' => $this->model,
            'messages' => $messages,
        ];
        empty($this->isLogging) || $this->storeLog($chatOpenAI);

        $result = OpenAI::chat()->create($chatOpenAI);

        return trim($result->choices[0]->message->content);
    }

    public function answer($developer, $messages, $question, $knowledge)
    {
        $developerContent = [];
        $developerContent[] = [
            'role' => 'developer',
            'content' => $developer,
        ];
        $developerContent[] = [
            'role' => 'developer',
            'content' => "# ナレッジ\n```json\n".json_encode($knowledge, JSON_UNESCAPED_UNICODE)."\n```",
        ];

        $messages = array_merge($developerContent, $messages);

        $messages[] = [
            'role' => 'user',
            'content' => $question,
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

        $messages = [];
        $messages[] = [
            'role' => 'developer',
            'content' => $developer,
        ];
        $messages[] = [
            'role' => 'developer',
            'content' => "# 翻訳したい文章\n".$answer,
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

        $langCode = self::preferredLanguage($acceptLanguage);

        return $langCode ?? app()->getLocale();
    }

    /**
     * Accept-Languageヘッダーから優先度の高い言語コードを取り出す。
     * 品質値（;q=）は並べ替えに使うだけで言語コードには含めない。
     */
    private static function preferredLanguage(string $acceptLanguage): ?string
    {
        $candidates = [];

        foreach (explode(',', $acceptLanguage) as $order => $language) {
            $parameters = explode(';', $language);
            $languageRange = strtolower(trim(array_shift($parameters)));

            // ワイルドカードや空の指定は候補にしない
            if ($languageRange === '' || $languageRange === '*') {
                continue;
            }

            $langCode = explode('-', $languageRange)[0];
            if (! preg_match('/^[a-z]{1,8}$/', $langCode)) {
                continue;
            }

            $quality = self::extractLanguageQuality($parameters);
            if ($quality <= 0) {
                // q=0 は「受け入れない」という意味なので除外する
                continue;
            }

            $candidates[] = ['code' => $langCode, 'quality' => $quality, 'order' => $order];
        }

        if (empty($candidates)) {
            return null;
        }

        // 品質値の降順、同値ならヘッダーの記載順を保つ
        usort($candidates, function ($left, $right) {
            return [$right['quality'], $left['order']] <=> [$left['quality'], $right['order']];
        });

        return $candidates[0]['code'];
    }

    /**
     * @param  array<int, string>  $parameters  ";" で分割した品質値などの指定
     */
    private static function extractLanguageQuality(array $parameters): float
    {
        foreach ($parameters as $parameter) {
            [$name, $value] = array_pad(explode('=', $parameter, 2), 2, null);

            if (strtolower(trim((string) $name)) !== 'q') {
                continue;
            }

            $value = trim((string) $value);

            return is_numeric($value) ? (float) $value : 1.0;
        }

        return 1.0;
    }

    private function detectLanguage($question, $answer)
    {
        $messages = [];
        $messages[] = [
            'role' => 'developer',
            'content' => file_get_contents(resource_path('prompt/detectLanguage.md')),
        ];
        $messages[] = [
            'role' => 'developer',
            'content' => "# 言語を特定するための文章\n".mb_substr($question, 0, 1000),
        ];
        $messages[] = [
            'role' => 'developer',
            'content' => "# 翻訳したい文章\n".mb_substr($answer, 0, 1000),
        ];

        $chatOpenAI = [
            'model' => $this->model,
            'messages' => $messages,
        ];
        empty($this->isLogging) || $this->storeLog($chatOpenAI);

        $result = OpenAI::chat()->create($chatOpenAI);

        return trim($result->choices[0]->message->content);
    }

    public function log($isLogging)
    {
        $this->isLogging = $isLogging;

        return $this;
    }

    private function storeLog($chatOpenAI)
    {
        file_put_contents(storage_path('logs/chatOpenAI.log'), json_encode($chatOpenAI, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
    }
}
