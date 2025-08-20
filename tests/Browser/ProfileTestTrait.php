<?php

namespace Tests\Browser;

trait ProfileTestTrait
{
    private function updateAvator($browser, $name): void
    {
        // サイドメニューのプロフィールが非表示の時は、サイドメニューの $name のリンクをクリックする
        try {
            $browser->clickLink('プロフィール')->pause(500);
        } catch (\Throwable $e) {
            $browser->clickLink($name)->pause(500)->clickLink('プロフィール')->pause(500);
        }

        // Dropzone に logo.png アップロードする
        $browser->scrollIntoView('.dropzone')->pause(500)->attach('input.dz-hidden-input', base_path('tests/Browser/upload/logo.png'));

        // 確認ボタンをクリックする、モーダル内の更新ボタンをクリックする
        $browser->click('button[data-bs-target="#modalUpdate"]')->pause(500)
            ->whenAvailable('#modalUpdate', function ($modal) {
                $modal->click('button[type="submit"].btn.btn-primary')->pause(500);
            });
    }
}
