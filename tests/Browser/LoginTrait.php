<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;

trait LoginTrait
{
    protected function login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visitRoute('login');

            $browser->type('email', 'admin')
            ->type('password', 'admin')
            ->press('ログイン');

            $browser->assertSee('管理トップ');
        });
    }

    protected function logout(): void
    {
        $this->browse(function (Browser $browser) {
            // ログアウトボタンをクリック
            $browser->click('#headicons > ul > li:nth-child(3) > a');

            $browser->waitFor('#modal_logout')
            ->pause(500)
            ->press('ログアウト');

            $browser->assertSee('ログイン');
        });
    }
}
