<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;

trait UserTrait
{
    protected function searchUser($search): void
    {
        $this->browse(function (Browser $browser) use ($search) {
            $browser->type('search', $search)
            ->press('#inmaincontents > form > div > div > span:nth-child(1) > button');

            $browser->assertSee($search);
        });
    }

    protected function storeUser($user, $message): void
    {
        $this->browse(function (Browser $browser) use ($user, $message) {
            $browser->clickLink('新規作成');

            $browser->type('email', $user->email)
            ->type('password', $user->password)
            ->type('repassword', $user->password)
            ->press('確認');

            $browser->waitFor('#modal_store')
            ->pause(500)
            ->press('#modal_store > div > div > div.modal-footer > button.btn.btn-primary.btn-lg');

            $browser->assertSee($message);
        });
    }

    protected function updateUser($user, $name, $message): void
    {
        $this->browse(function (Browser $browser) use ($user, $message) {
            $browser->clickLink($user->email);

            $browser->type('name', $user->email.'_2')
            ->press('確認');

            $browser->waitFor('#modal_update')
            ->pause(500)
            ->press('#modal_update > div > div > div.modal-footer > button.btn.btn-primary.btn-lg');

            $browser->assertSee($message);
        });
    }

    protected function destroyUser($user, $message): void
    {
        $this->browse(function (Browser $browser) use ($user, $message) {
            $browser->clickLink($user->email);

            $browser->press('削除');

            $browser->waitFor('#modal_destroy')
            ->pause(500)
            ->press('#modal_destroy > div > div > div.modal-footer > button.btn.btn-danger.btn-lg');

            $browser->assertSee($message);
        });
    }

    protected function invalidUser($rows, $message): void
    {
        $this->browse(function (Browser $browser) use ($rows, $message) {
            $browser->clickTableCell($rows, 1, 'button');

            $browser->waitFor('#modal_toggle')
            ->pause(500)
            ->press('#modal_toggle > div > div > div.modal-footer > button.btn.btn-warning.btn-lg');

            $browser->assertSee($message);
        });
    }

    protected function validUser($rows, $message): void
    {
        $this->browse(function (Browser $browser) use ($rows, $message) {
            $browser->clickTableCell($rows, 1, 'button');

            $browser->waitFor('#modal_toggle')
            ->pause(500)
            ->press('#modal_toggle > div > div > div.modal-footer > button.btn.btn-success.btn-lg');

            $browser->assertSee($message);
        });
    }

    protected function selectUser($rows, $message): void
    {
        $this->browse(function (Browser $browser) use ($rows, $message) {
            $browser->clickTableCell($rows, 3)
            ->press('削除');

            $browser->waitFor('#modal_destroy')
            ->pause(500)
            ->press('#modal_destroy > div > div > div.modal-footer > button.btn.btn-danger.btn-lg');

            $browser->assertSee($message);
        });
    }
}
