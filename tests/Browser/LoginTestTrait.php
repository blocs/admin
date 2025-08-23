<?php

namespace Tests\Browser;

trait LoginTestTrait
{
    private function login($browser, $email, $password): void
    {
        /*
            $email = 'admin';
            $password = 'admin';
        */

        // http://localhost/login に移動する
        $browser->visit('http://localhost/login')->pause(500);

        // ユーザーID $email, パスワード $password を入力して、ログインする
        $browser->type('email', $email)
            ->type('password', $password)
            ->click('button[type="submit"]')->pause(500);
    }

    private function logout($browser, $name): void
    {
        /*
            $name = 'admin';
        */

        // サイドメニューのログアウトが非表示の時は、$name リンクをクリックした後に、ログアウトリンクをクリックする
        try {
            $browser->clickLink('ログアウト')->pause(500);
        } catch (\Throwable $e) {
            $browser->clickLink($name)->pause(500)->clickLink('ログアウト')->pause(500);
        }

        // モーダル内のログアウトボタンをクリックする
        $browser->whenAvailable('#modalLogout', function ($modal) {
            $modal->click('button.btn.btn-primary')->pause(500);
        });
    }
}
