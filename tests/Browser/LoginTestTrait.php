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
                ->click('button[type="submit"]')
                ->pause(500);
    }

    private function logout($browser, $name): void
    {
        /*
            $name = 'admin';
        */

        // サイドメニューのログアウトが非表示の時は、サイドメニューの $name のリンクをクリックする
        try {
            $browser->click('a[data-bs-target="#modalLogout"]')->pause(500);
        } catch (\Throwable $e) {
            $browser->clickLink($name)->pause(500);
        }

        // サイドメニューのログアウトリンクをクリックして、モーダル内のログアウトボタンをクリックする
        $browser->click('#sidenav-main a[data-bs-target="#modalLogout"]')->pause(500)
            ->whenAvailable('#modalLogout', function ($modal) {
                $modal->click('button[formaction="http://localhost/logout"]')->pause(500);
            });
    }
}
