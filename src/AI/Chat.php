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

        return $this->complete(array_merge([
            $this->message('developer', file_get_contents(resource_path('prompt/question.md'))),
            $this->message('developer', "# ナレッジ\n".$knowledge),
        ], $messages));
    }

    public function answer($developer, $messages, $question, $knowledge)
    {
        return $this->complete(array_merge([
            $this->message('developer', $developer),
            $this->message('developer', "# ナレッジ\n```json\n".json_encode($knowledge, JSON_UNESCAPED_UNICODE)."\n```"),
        ], $messages, [
            $this->message('user', $question),
        ]));
    }

    public function translate($question, $answer, $questionLang = null)
    {
        if (empty($questionLang)) {
            // 応答が1行だけ・空行入りでも落ちないよう、空でない行だけを取り出す
            $language = $this->detectLanguage($question, $answer);
            $languages = array_values(array_filter(array_map('trim', preg_split('/\R/u', $language) ?: []), 'strlen'));
            if ($languages === []) {
                // 言語を特定できなければ翻訳しない
                return $answer;
            }

            $questionLang = $languages[0];
            if (isset($languages[1]) && $questionLang === $languages[1]) {
                // 翻訳は不要
                return $answer;
            }
        }

        $developer = file_get_contents(resource_path('prompt/translate.md'));

        // 利用者由来の文章は指示として扱われないよう、user ロールで渡す
        return $this->complete([
            $this->message('developer', str_replace('{{language}}', trim($questionLang), $developer)),
            $this->message('user', "# 翻訳したい文章\n".$answer),
        ]);
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
        // 利用者由来の文章は指示として扱われないよう、user ロールで渡す
        return $this->complete([
            $this->message('developer', file_get_contents(resource_path('prompt/detectLanguage.md'))),
            $this->message('user', "# 言語を特定するための文章\n".mb_substr($question, 0, 1000)),
            $this->message('user', "# 翻訳したい文章\n".mb_substr($answer, 0, 1000)),
        ]);
    }

    public function log($isLogging)
    {
        $this->isLogging = $isLogging;

        return $this;
    }

    /**
     * @return array{role: string, content: mixed}
     */
    private function message(string $role, $content): array
    {
        return ['role' => $role, 'content' => $content];
    }

    /**
     * メッセージを送り、応答の本文を返す。ログが有効なら送信内容を記録する。
     *
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function complete(array $messages): string
    {
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
