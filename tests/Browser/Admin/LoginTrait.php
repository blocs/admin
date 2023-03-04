<?php

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;

trait LoginTrait
{
    protected function login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visitRoute('login')
            ->type('email', 'admin')
            ->type('password', 'admin')
            ->press('ログイン')
            ->assertSee('管理トップ');
        });
    }

    protected function logout(): void
    {
        $this->browse(function (Browser $browser) {
            // ログアウトボタンをクリック
            $browser->clickAtXpath('//*[@id="headicons"]/ul/li[3]/a')
            ->waitFor('#modal_logout')
            ->press('ログアウト')
            ->assertSee('ログイン');
        });
    }
}
