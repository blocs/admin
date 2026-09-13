<?php

namespace Blocs\Tests;

use Blocs\AI\Chat;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Chat::checkLocale() が Accept-Language から言語コードだけを取り出せるかを確認する
 */
class ChatLocaleTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public static function acceptLanguages(): array
    {
        return [
            'ヘッダーなし' => [null, 'en'],
            '単純な指定' => ['ja', 'ja'],
            '地域付き' => ['ja-JP', 'ja'],
            '品質値付き' => ['ja;q=0.9', 'ja'],
            '品質値の高い方を選ぶ' => ['en;q=0.5,ja;q=0.9', 'ja'],
            '品質値が同じなら先頭を選ぶ' => ['en,ja', 'en'],
            '品質値省略は1として扱う' => ['en;q=0.8,ja', 'ja'],
            'ワイルドカードは飛ばす' => ['*,ja', 'ja'],
            'q=0は受け入れない' => ['en;q=0,ja;q=0.1', 'ja'],
            '空の要素は飛ばす' => [',,ja-JP', 'ja'],
            '空白を含む' => [' ja-JP ; q=0.7 , en ; q=0.6 ', 'ja'],
            '不正な値だけならロケールに戻す' => ['*', 'en'],
        ];
    }

    #[Test, DataProvider('acceptLanguages')]
    public function test_check_locale(?string $acceptLanguage, string $expected): void
    {
        $this->bootApplication($acceptLanguage);

        $this->assertSame($expected, Chat::checkLocale());
    }

    private function bootApplication(?string $acceptLanguage): void
    {
        $application = new Application(dirname(__DIR__, 2));
        $application->instance('config', new Repository(['app' => ['locale' => 'en']]));

        $server = [];
        $acceptLanguage === null || $server['HTTP_ACCEPT_LANGUAGE'] = $acceptLanguage;
        $application->instance('request', Request::create('/', 'GET', [], [], [], $server));

        Container::setInstance($application);
    }
}
