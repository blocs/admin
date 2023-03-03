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
}
