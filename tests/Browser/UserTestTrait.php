<?php

namespace Tests\Browser;

trait UserTestTrait
{
    private function gotoUser($browser): void
    {
        // サイドメニューにユーザー管理が非表示の時は、管理トップをクリックした後に、ユーザー管理をクリックする
        try {
            $browser->click('a[href="http://localhost/admin/user"]')->pause(500);
        } catch (\Throwable $e) {
            $browser->clickLink('管理トップ')->pause(500)
                ->click('a[href="http://localhost/admin/user"]')->pause(500);
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
        $browser->type('name', fake()->name())
                ->click('input.form-check-input[name="role[]"][value="admin"]')->pause(500);

        // 確認ボタンをクリックして、モーダル内の新規登録ボタンをクリックする
        $browser->click('button[data-bs-target="#modalStore"]')->pause(500)
            ->whenAvailable('#modalStore', function ($modal) {
                $modal->click('button.btn.btn-primary')->pause(500);
            });
    }

    private function searchUser($browser, $email): void
    {
        /*
            $email = 'admin';
        */

        $this->gotoUser($browser);

        // テーブルの上に虫眼鏡アイコンがある時は、虫眼鏡アイコンをクリックする
        try {
            $browser->click('a.summary-search')->pause(500);
        } catch (\Throwable $e1) {
            try {
                $browser->click('i.fa-search')->pause(500);
            } catch (\Throwable $e2) {
            }
        }

        // 検索フィールドに $email を入力して、検索ボタンをクリックする
        $browser->type('input[name="search"]', $email)
            ->click('button.btn.btn-outline-secondary.m-0')
            ->pause(500);
    }

    private function updateUser($browser, $email, $name): void
    {
        /*
            $name = fake()->name();
        */

        $this->gotoUser($browser);
        $this->searchUser($browser, $email);

        // 検索結果の一行目の編集アイコンをクリックする
        $browser->click('table.dataTable-table tbody tr:first-child a[href$="/edit"]')->pause(500);

        // 名前に $name を入力して、確認ボタンをクリックして、モーダル内の更新ボタンをクリックする
        $browser->type('name', $name)
            ->click('button[data-bs-target="#modalUpdate"]')->pause(500)
            ->whenAvailable('#modalUpdate', function ($modal) {
                $modal->click('button.btn.btn-primary')->pause(500);
            });

        // クリアボタンをクリックする
        $browser->click('button.clear')->pause(500);
    }

    private function destroyUser($browser, $email): void
    {
        $this->gotoUser($browser);
        $this->searchUser($browser, $email);

        // 検索結果の一行目の削除アイコンをクリックする
        $browser->click('table.dataTable-table tbody tr:first-child td.text-nowrap a:nth-of-type(3)')->pause(500);

        // 左下の削除ボタンをクリックして、モーダル内の削除ボタンをクリックする
        $browser->click('button[data-bs-target="#modalDestroy"]')->pause(500)
            ->whenAvailable('#modalDestroy', function ($modal) {
                $modal->click('button.btn.btn-danger')->pause(500);
            });

        // クリアボタンをクリックする
        $browser->click('button.clear')->pause(500);
    }

    private function deleteUser($browser, $email): void
    {
        $this->gotoUser($browser);
        $this->searchUser($browser, $email);

        // 検索結果の一行目のチェックボックスをクリックする
        $browser->click('input[name="users[0][selectedRows][]"]')->pause(500);

        // テーブルの下の削除ボタンをクリックする、モーダル内の削除ボタンをクリックする
        $browser->click('button[data-bs-target="#modalDestroy"]')->pause(500)
            ->whenAvailable('#modalDestroy', function ($modal) {
                $modal->click('button.btn.btn-danger')->pause(500);
            });

        // クリアボタンをクリックする
        $browser->click('button.clear')->pause(500);
    }

    private function invalidUser($browser, $email): void
    {
        $this->gotoUser($browser);
        $this->searchUser($browser, $email);

        // 検索結果の一行目の凍結リンクをクリックして、モーダル内の凍結ボタンをクリックする
        $browser->click('table.dataTable-table tbody tr:first-child a[data-bs-target="#modalInactivate"]')->pause(500)
            ->whenAvailable('#modalInactivate', function ($modal) {
                $modal->click('button.btn.btn-warning')->pause(500);
            });

        // クリアボタンをクリックする
        $browser->click('button.clear')->pause(500);
    }

    private function validUser($browser, $email): void
    {
        $this->gotoUser($browser);
        $this->searchUser($browser, $email);

        // 検索結果の一行目の凍結解除リンクをクリックして、モーダル内の凍結解除ボタンをクリックする
        $browser->click('table.dataTable-table tbody tr:first-child a[data-bs-target="#modalActivate"]')->pause(500)
            ->whenAvailable('#modalActivate', function ($modal) {
                $modal->click('button.btn-success')->pause(500);
            });

        // クリアボタンをクリックする
        $browser->click('button.clear')->pause(500);
    }
}
