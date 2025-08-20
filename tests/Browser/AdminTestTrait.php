<?php

namespace Tests\Browser;

trait AdminTestTrait
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

    private function gotoUser($browser): void
    {
        // サイドメニューにユーザー管理が非表示の時は、サイドメニューの管理トップをクリックする
        // ユーザー管理をクリックする
        try {
            $browser->clickLink('ユーザー管理')->pause(500);
        } catch (\Throwable $e) {
            $browser->clickLink('管理トップ')->pause(500)
                    ->clickLink('ユーザー管理')->pause(500);
        }
    }

    private function createUser($browser, $email, $password): void
    {
        /*
            $email = fake()->email();
            $password = fake()->password();
        */

        $this->gotoUser($browser);

        // 新規作成をクリックする
        $browser->clickLink('新規作成')->pause(500);

        // ユーザーIDに $email, パスワードとパスワード（確認）に $password を入力する
        $browser->type('email', $email)
            ->type('password', $password)
            ->type('repassword', $password);

        // 名前に適当な名前を入力して、admin のチェックボックスをチェックする
        $browser->type('name', \fake()->name())
                ->click('input.form-check-input[name="role[]"][value="admin"]')->pause(500);

        // 確認ボタンをクリックする、モーダル内の新規登録ボタンをクリックする
        $browser->click('button[data-bs-target="#modalStore"]')->pause(500)
            ->whenAvailable('#modalStore', function ($modal) {
                $modal->click('button[formaction="http://localhost/admin/user"]')->pause(500);
            });
    }

    private function searchUser($browser, $email): void
    {
        /*
            $email = 'admin';
        */

        $this->gotoUser($browser);

        // テーブルの上に虫眼鏡のアイコンがある時は、虫眼鏡アイコンをクリックする
        try {
            $browser->click('.summary-search')->pause(500);
        } catch (\Throwable $e) {
        }

        // 検索フィールドに $email を入力して、検索ボタンをクリックする
        $browser->type('search', $email)
                ->press('検索')->pause(500);
    }

    private function updateUser($browser, $email, $name): void
    {
        /*
            $email = fake()->email();
            $name = fake()->name();
        */

        $this->gotoUser($browser);
        $this->searchUser($browser, $email);

        // 検索結果の一行目の編集アイコンをクリックする
        $browser->click('table.dataTable-table tbody tr:first-child a[href*="/admin/user/"][href$="/edit"]')->pause(500);

        // 名前を $name 入力して、確認ボタンをクリックする、モーダル内の更新ボタンをクリックする
        $browser->type('name', $name)
            ->click('button[data-bs-target="#modalUpdate"]')->pause(500)
            ->whenAvailable('#modalUpdate', function ($modal) {
                $modal->click('button[type="submit"]')->pause(500);
            });

        // クリアボタンをクリックする
        $browser->click('button.clear')->pause(500);
    }

    private function destroyUser($browser, $email): void
    {
        $this->gotoUser($browser);
        $this->searchUser($browser, $email);

        // 検索結果の一行目の trash アイコンをクリックする
        $browser->click('table.dataTable-table tbody tr:first-child td.text-nowrap a:nth-of-type(3)')->pause(500);

        // 左下の削除ボタンをクリックする、モーダル内の削除ボタンをクリックする
        $browser
            ->click('button[data-bs-target="#modalDestroy"]')->pause(500)
            ->whenAvailable('#modalDestroy', function ($modal) {
                $modal->click('button[type="submit"]')->pause(500);
            });

        // クリアボタンをクリックする
        $browser->click('button.clear')->pause(500);
    }

    private function deleteUser($browser, $email): void
    {
        $this->gotoUser($browser);
        $this->searchUser($browser, $email);

        // 検索結果の一行目のチェックボックスをクリックする
        $browser->click('tbody tr:first-child input.form-check-input')->pause(500);

        // テーブルの下の削除ボタンをクリックする、モーダル内の削除ボタンをクリックする
        $browser->click('button[data-bs-target="#modalDestroy"]')->pause(500)
            ->whenAvailable('#modalDestroy', function ($modal) {
                $modal->click('button[formaction="http://localhost/admin/user/select"]')->pause(500);
            });

        // クリアボタンをクリックする
        $browser->click('button.clear')->pause(500);
    }

    private function invalidUser($browser, $email): void
    {
        $this->gotoUser($browser);
        $this->searchUser($browser, $email);

        // 検索結果の一行目のユーザーの凍結リンクをクリックして、モーダル内の凍結ボタンをクリックする
        $browser
            ->click('table.dataTable-table tbody tr:first-child a[data-bs-target="#modalInactivate"]')
            ->pause(500)
            ->waitFor('#modalInactivate.show')
            ->within('#modalInactivate', function ($modal) {
                $modal->press('凍結')->pause(500);
            });

        // クリアボタンをクリックする
        $browser->click('button.clear')->pause(500);
    }

    private function validUser($browser, $email): void
    {
        $this->gotoUser($browser);
        $this->searchUser($browser, $email);

        // 検索結果の一行目のユーザーの凍結解除リンクをクリックして、モーダル内の凍結解除ボタンをクリックする
        $browser->click('table tbody tr:first-child a[data-bs-target="#modalActivate"]')
            ->pause(500)
            ->whenAvailable('#modalActivate', function ($modal) {
                $modal->press('凍結解除')->pause(500);
            });

        // クリアボタンをクリックする
        $browser->click('button.clear')->pause(500);
    }

    private function updateAvator($browser, $name): void
    {
        /*
            $name = 'admin';
        */

        // サイドメニューのプロフィールが非表示の時は、サイドメニューの $name のリンクをクリックする
        try {
            $browser->clickLink('プロフィール')->pause(500);
        } catch (\Throwable $e) {
            $browser->clickLink($name)->pause(500)->clickLink('プロフィール')->pause(500);
        }

        // Dropzone に logo.png アップロードする
        $browser->scrollIntoView('.dropzone')->pause(500)->attach('input.dz-hidden-input', \base_path('tests/Browser/logo.png'));

        // 確認ボタンをクリックする、モーダル内の更新ボタンをクリックする
        $browser->click('button[data-bs-target="#modalUpdate"]')->pause(500)
            ->whenAvailable('#modalUpdate', function ($modal) {
                $modal->click('button[type="submit"].btn.btn-primary')->pause(500);
            });
    }

    private function assertDuplicateUserIdError($browser): void
    {
        // 重複登録エラーのメッセージを確認する
        $browser->assertSee('このユーザーIDはすでに登録されています。')->pause(500);
    }

    private function assertLoginFailed($browser): void
    {
        // ログイン失敗メッセージを確認する
        $browser->assertSee('ログインに失敗しました。')->pause(500);
    }
}
