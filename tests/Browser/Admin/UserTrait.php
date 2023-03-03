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
            ->press('確認')
            ->whenAvailable('#modal_store', function (Browser $modal) {
                $modal->assertSee('データの新規登録')
                ->press('新規登録');
            });

            isset($message) && $browser->assertSee($message);
        });
    }

    protected function update_user($user, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($user, $message) {
            $browser->clickLink($user->email)
            ->type('name', $user->email.'_2')
            ->press('確認')
            ->whenAvailable('#modal_update', function (Browser $modal) {
                $modal->assertSee('データの更新')
                ->press('更新');
            });

            isset($message) && $browser->assertSee($message);
        });
    }

    protected function destroy_user($user, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($user, $message) {
            $browser->clickLink($user->email)
            ->pressAndWaitFor('削除')
            ->whenAvailable('#modal_destroy', function (Browser $modal) {
                $modal->assertSee('データの削除')
                ->press('削除');
            });

            isset($message) && $browser->assertSee($message);
        });
    }

    protected function invalid_user($rows, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($rows, $message) {
            $browser->clickTableCell($rows, 1, 'button')
            ->whenAvailable('#modal_toggle', function (Browser $modal) {
                $modal->assertSee('ユーザーの凍結')
                ->press('凍結');
            });

            isset($message) && $browser->assertSee($message);
        });
    }

    protected function valid_user($rows, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($rows, $message) {
            $browser->clickTableCell($rows, 1, 'button')
            ->whenAvailable('#modal_toggle', function (Browser $modal) {
                $modal->assertSee('ユーザーの凍結解除')
                ->press('凍結解除');
            });

            isset($message) && $browser->assertSee($message);
        });
    }

    protected function select_user($rows, $message = null): void
    {
        $this->browse(function (Browser $browser) use ($rows, $message) {
            $browser->clickTableCell($rows, 3)
            ->pressAndWaitFor('削除')
            ->whenAvailable('#modal_destroy', function (Browser $modal) {
                $modal->assertSee('データの削除')
                ->press('削除');
            });

            isset($message) && $browser->assertSee($message);
        });
    }
}
