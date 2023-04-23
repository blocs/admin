<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminTest extends DuskTestCase
{
    use MacroTrait;
    use LoginTrait;
    use UserTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // マクロを登録
        $this->macro();

        $this->login();
    }

    /**
     * A Dusk test example.
     */
    public function testユーザー管理(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->clickLink('ユーザー管理');
        });

        $testUser = new \stdClass();
        $testUser->email = time();
        $testUser->password = time();

        // テストユーザーを追加
        $this->storeUser($testUser, '「'.$testUser->email.'」を登録しました。');

        // テストユーザーを検索
        $this->searchUser($testUser->email);

        // テストユーザーを凍結
        $this->invalidUser(1, '「'.$testUser->email.'」を無効にしました。');

        // テストユーザーを凍結解除
        $this->validUser(1, '「'.$testUser->email.'」を有効にしました。');

        // テストユーザーを削除
        $this->destroyUser($testUser, '1 件のデータを削除しました。');

        $testUser2 = new \stdClass();
        $testUser2->email = time();
        $testUser2->password = time();

        // テストユーザーを追加
        $this->storeUser($testUser2, '「'.$testUser2->email.'」を登録しました。');

        // テストユーザーを検索
        $this->searchUser($testUser2->email);

        // テストユーザーを更新
        $this->updateUser($testUser2, $testUser2->email.'_2', '「'.$testUser2->email.'」を更新しました。');

        // 1行目のユーザーを選択して削除
        $this->selectUser(1, '1 件のデータを削除しました。');

        // 重複エラー
        $this->storeUser($testUser2, 'このユーザーIDはすでに登録されています。');
    }

    public function testプロフィール(): void
    {
        $this->browse(function (Browser $browser) {
            // プロフィールボタンをクリック
            $browser->click('#headicons > ul > li:nth-child(2) > a');

            // 画像を添付
            $browser->uploadFile(__DIR__.'/logo.png');

            // 画像を削除
            $browser->deleteFile();

            // 名前を更新
            $browser->type('name', 'testName')
            ->press('確認');

            // 更新ボタンをクリック
            $browser->waitFor('#modal_update')
            ->pause(500)
            ->press('#modal_update > div > div > div.modal-footer > button.btn.btn-primary.btn-lg');

            // メッセージをチェック
            $browser->assertSee('プロフィールを更新しました。');
        });
    }

    protected function tearDown(): void
    {
        $this->logout();

        parent::tearDown();
    }
}
