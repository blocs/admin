<?php

namespace Tests\Browser;

trait ProfileTestTrait
{
    private function updateAvator($browser, $name): void
    {
        /*
            $name = 'admin';
        */

        // サイドメニューのプロフィールが非表示の時は、$name のリンクをクリックした後に、プロフィールをクリックする
        try {
            $browser->clickLink('プロフィール')->pause(500);
        } catch (\Throwable $e) {
            $browser->clickLink($name)->pause(500)->clickLink('プロフィール')->pause(500);
        }

        // 画面の一番下までスクロールして、Dropzone に base_path('tests/Browser/upload/logo.png') をアップロードする
        $browser->scrollIntoView('footer')->pause(500)
            ->attach('input.dz-hidden-input', base_path('tests/Browser/upload/logo.png'))->pause(500);

        // 確認ボタンをクリックして、モーダル内の更新ボタンをクリックする
        $browser->click('button[data-bs-target="#modalUpdate"]')->pause(500)
            ->whenAvailable('#modalUpdate', function ($modal) {
                $modal->click('button.btn.btn-primary')->pause(500);
            });
    }
}
