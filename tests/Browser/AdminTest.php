<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminTest extends DuskTestCase
{
    use LoginTestTrait;
    use ProfileTestTrait;
    use UserTestTrait;

    public function testAdmin(): void
    {
        $this->browse(function (Browser $browser) {
            // ユーザーID：admin、パスワード:adminでログインする
            $this->login($browser, 'admin', 'admin');

            // $email に適当なID、$password に適当なパスワードを代入する
            $email = fake()->email();
            $password = fake()->password();

            // 新規ユーザーを登録する
            $this->createUser($browser, $email, $password);

            // ログアウトする
            $this->logout($browser, 'admin');

            // ユーザーID：$email、パスワード:$passwordでログインする
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

            // ユーザーID：$emailのユーザーを無効にする
            $this->invalidUser($browser, $email);

            // ユーザーID：$emailのユーザーを有効にする
            $this->validUser($browser, $email);

            // ユーザーID：$emailのユーザーを一括削除する
            $this->deleteUser($browser, $email);

            // 新規ユーザーを登録する
            $this->createUser($browser, $email, $password);

            // ユーザーID：$emailのユーザーを削除する
            $this->destroyUser($browser, $email);

            // ログアウトする
            $this->logout($browser, $name);

            // ユーザーID：$email、パスワード:$passwordでログインする
            $this->login($browser, $email, $password);

            // ログインに失敗しました。が表示されていることを確認する
            $browser->assertSee('ログインに失敗しました。');
        });
    }
}
