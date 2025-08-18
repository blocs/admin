<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminTest extends DuskTestCase
{
    public function testAdmin(): void
    {
        $this->browse(function (Browser $browser) {
            // ログインする
            $this->login($browser, 'admin', 'admin');

            // $email に適当なID、$password に適当なパスワードを代入する
            $email = fake()->email();
            $password = fake()->password();

            // 新規ユーザーを登録する
            $this->createUser($browser, $email, $password);

            // ログアウトする
            $this->logout($browser);

            // ログインする
            $this->login($browser, $email, $password);

            // 新規ユーザーを登録する
            $this->createUser($browser, $email, $password);

            // このユーザーIDはすでに登録されています。が表示されていることを確認する（重複チェック）
            $browser->assertSee('このユーザーIDはすでに登録されています。');

            // 適当な名前を $name に代入する
            $name = fake()->name();

            // ユーザーを更新する
            $this->updateUser($browser, $email, $name);

            // アバター画像を更新する
            $this->updateAvator($browser, $name);

            // ユーザーを無効にする
            $this->invalidUser($browser, $email);

            // ユーザーを有効にする
            $this->validUser($browser, $email);

            // ユーザーを一括削除する
            $this->deleteUser($browser, $email);

            // 新規ユーザーを登録する
            $this->createUser($browser, $email, $password);

            // ユーザーを削除する
            $this->destroyUser($browser, $email);

            // ログアウトする
            $this->logout($browser);

            // ログインする
            $this->login($browser, $email, $password);

            // ログインに失敗しました。が表示されていることを確認する
            $browser->assertSee('ログインに失敗しました。');
        });
    }

    private function login($browser, $email, $password): void
    {
        // http://localhost/login に移動する
        $browser->visit('http://localhost/login');

        // ID:admin, Password:adminを入力して、ログインする
        $browser->type('input[name="email"]', $email)
            ->type('input[name="password"]', $password)
            ->press('ログイン')
            ->pause(1000);
    }

    private function logout($browser): void
    {
        // サイドメニューのログアウトが非表示の時は、サイドメニューのユーザーIDのリンクをクリックする、リンクはCSSセレクターで指定する
        if (!$browser->seeLink('ログアウト')) {
            $browser->click('a.nav-link.text-white:has(img.avatar)')
                ->pause(1000);
        }

        // サイドメニューのログアウトリンクをクリックして、モーダル内のログアウトボタンをクリック
        $browser->clickLink('ログアウト')
            ->waitFor('#modalLogout')
            ->press('#modalLogout .btn-primary')
            ->pause(1000);
    }

    private function gotoUser($browser): void
    {
        // サイドメニューにユーザー管理が非表示の時は、サイドメニューの管理トップをクリックする
        if (!$browser->seeLink('ユーザー管理')) {
            $browser->clickLink('管理トップ')
                ->pause(1000);
        }

        // ユーザー管理をクリックする
        $browser->clickLink('ユーザー管理')
            ->pause(1000);
    }

    private function createUser($browser, $email, $password): void
    {
        $this->gotoUser($browser);

        // 新規作成をクリックする
        $browser->clickLink('新規作成')
            ->pause(1000);

        // IDに $name, パスワードに $password を入力する
        $browser->type('input[name="email"]', $email)
            ->type('input[name="password"]', $password)
            ->type('input[name="repassword"]', $password)
            ->pause(1000);

        // 名前に適当な名前を入力して、admin のチェックボックスをチェックする
        $browser->type('input[name="name"]', fake()->name())
            ->check('input[name="role[]"][value="admin"]');

        // 確認ボタンをクリックする、モーダル内の新規登録ボタンをクリックする
        $browser->click('button[data-bs-target="#modalStore"]')
            ->waitFor('#modalStore')
            ->press('#modalStore .btn-primary')
            ->pause(1000);
    }

    private function updateUser($browser, $email, $name): void
    {
        $this->gotoUser($browser);

        // ユーザーを検索
        $this->searchUser($browser, $email);

        // 検索結果の一行目の編集アイコンをクリックする
        $browser->click('a.me-3 i.fa-pen')
            ->pause(1000);

        // 名前を $name 入力して、確認ボタンをクリックする、モーダル内の更新ボタンをクリックする
        $browser->type('input[name="name"]', $name)
            ->press('確認')
            ->waitFor('#modalUpdate')
            ->press('#modalUpdate .btn-primary');

        // クリアボタンをクリックする
        $browser->press('クリア')
            ->pause(1000);
    }

    private function updateAvator($browser, $name): void
    {
        // サイドメニューにプロフィールのリンクが非表示の時は、サイドメニューの $name のリンクをクリックする
        if (!$browser->seeLink('プロフィール')) {
            $browser->clickLink($name)
                ->pause(1000);
        }

        // サイドメニューのプロフィールをクリックする
        $browser->clickLink('プロフィール')
            ->pause(1000);

        // Dropzone に logo.png アップロードする
        $browser->attach('input.dz-hidden-input', base_path('vendor/blocs/admin/tests/Browser/logo.png'));

        // 確認ボタンをクリックする、モーダル内の更新ボタンをクリックする
        $browser->press('確認')
            ->waitFor('#modalUpdate')
            ->press('#modalUpdate .btn-primary')
            ->pause(1000);
    }

    private function searchUser($browser, $email): void
    {
        // サイドメニューにユーザー管理が非表示の時は、サイドメニューの管理トップをクリックする
        if (!$browser->seeLink('ユーザー管理')) {
            $browser->clickLink('管理トップ')
                ->pause(1000);
        }

        // ユーザー管理をクリックする
        $browser->clickLink('ユーザー管理')
            ->pause(1000);

        // テーブルの上の虫眼鏡アイコンをクリックする
        $browser->click('.summary-search');

        // 検索フィールドに $email を入力して、検索ボタンをクリックする
        $browser->type('input[name="search"]', $email)
            ->press('button.btn-outline-secondary.m-0')
            ->pause(1000);
    }

    private function deleteUser($browser, $email): void
    {
        $this->gotoUser($browser);

        // ユーザーを検索する
        $this->searchUser($browser, $email);

        // 検索結果の一行目のチェックボックスをクリックする
        $browser->click('input[name="users[0][selectedRows][]" ]');

        // テーブルの下の削除ボタンをクリックする、モーダル内の削除ボタンをクリックする
        $browser->click('.btn-outline-danger')
            ->waitFor('#modalDestroy')
            ->press('#modalDestroy .btn-danger');

        // クリアボタンをクリックする
        $browser->press('クリア')
            ->pause(1000);
    }

    private function destroyUser($browser, $email): void
    {
        $this->gotoUser($browser);

        // ユーザーを検索
        $this->searchUser($browser, $email);

        // 検索結果の一行目の trash アイコンをクリックする
        $browser->click('i.fa-solid.fa-trash');

        // 左下の削除ボタンをクリックする、モーダル内の削除ボタンをクリックする
        $browser->click('.btn-danger')  // 左下の削除ボタンをクリック
            ->waitFor('#modalDestroy')
            ->press('#modalDestroy .btn-danger')  // モーダル内の削除ボタンをクリック
            ->pause(1000);

        // クリアボタンをクリックする
        $browser->press('クリア')
            ->pause(1000);
    }

    private function invalidUser($browser, $email): void
    {
        $this->gotoUser($browser);

        // ユーザーを検索
        $this->searchUser($browser, $email);

        // 検索結果の一行目のユーザーの凍結リンクをクリックして、モーダル内の凍結ボタンをクリックする
        $browser->scrollIntoView('a.me-3[data-bs-target="#modalInactivate"]')
            ->click('a.me-3[data-bs-target="#modalInactivate"]')
            ->waitFor('#modalInactivate')
            ->press('button.btn-warning')
            ->pause(1000);

        // クリアボタンをクリックする
        $browser->press('クリア')
            ->pause(1000);
    }

    private function validUser($browser, $email): void
    {
        $this->gotoUser($browser);

        // ユーザーを検索
        $this->searchUser($browser, $email);

        // 検索結果の一行目のユーザーの凍結リンクをクリックして、モーダル内の凍結ボタンをクリックする
        $browser->scrollIntoView('a.me-3[data-bs-target="#modalActivate"]')
            ->click('a.me-3[data-bs-target="#modalActivate"]')
            ->waitFor('#modalActivate')
            ->press('button.btn-success')
            ->pause(1000);

        // クリアボタンをクリックする
        $browser->press('クリア')
            ->pause(1000);
    }
}
