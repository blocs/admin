<?php

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;

trait UserTrait
{
    protected function search_user($search): void
    {
        $this->browse(function (Browser $browser) use ($search) {
            $browser->type('search', $search)
            ->clickAtXPath('//*[@id="inmaincontents"]/form[1]/div/div/span[1]/button')
            ->assertSee($search);
        });
    }

    protected function store_user($user, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($user, $message) {
            $browser->clickLink('新規作成')
            ->type('email', $user->email)
            ->type('password', $user->password)
            ->type('repassword', $user->password)
            ->press('確認');

            $browser->waitFor('#modal_store')
            ->press('#modal_store > div > div > div.modal-footer > button.btn.btn-primary.btn-lg');

            isset($message) && $browser->assertSee($message);
        });
    }

    protected function update_user($user, $name, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($user, $message) {
            $browser->clickLink($user->email)
            ->type('name', $user->email.'_2')
            ->press('確認');

            $browser->waitFor('#modal_update')
            ->press('#modal_update > div > div > div.modal-footer > button.btn.btn-primary.btn-lg');

            isset($message) && $browser->assertSee($message);
        });
    }

    protected function destroy_user($user, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($user, $message) {
            $browser->clickLink($user->email)
            ->press('削除');

            $browser->waitFor('#modal_destroy')
            ->press('#modal_destroy > div > div > div.modal-footer > button.btn.btn-danger.btn-lg');

            isset($message) && $browser->assertSee($message);
        });
    }

    protected function invalid_user($rows, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($rows, $message) {
            $browser->clickTableCell($rows, 1, 'button');

            $browser->waitFor('#modal_toggle')
            ->press('#modal_toggle > div > div > div.modal-footer > button.btn.btn-warning.btn-lg');

            isset($message) && $browser->assertSee($message);
        });
    }

    protected function valid_user($rows, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($rows, $message) {
            $browser->clickTableCell($rows, 1, 'button');

            $browser->waitFor('#modal_toggle')
            ->press('#modal_toggle > div > div > div.modal-footer > button.btn.btn-success.btn-lg');

            isset($message) && $browser->assertSee($message);
        });
    }

    protected function select_user($rows, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($rows, $message) {
            $browser->clickTableCell($rows, 3)
            ->press('削除');

            $browser->waitFor('#modal_destroy')
            ->press('#modal_destroy > div > div > div.modal-footer > button.btn.btn-danger.btn-lg');

            isset($message) && $browser->assertSee($message);
        });
    }
}