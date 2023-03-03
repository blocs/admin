<?php

namespace Tests\Browser\Admin;

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

        // ログイン
        $this->login();
    }

    /**
     * A Dusk test example.
     */
    public function testユーザー管理(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->clickMenu('ユーザー管理');
        });

        $testUser = new \stdClass();
        $testUser->email = time();
        $testUser->password = time();

        // テストユーザーを追加
        $this->store_user($testUser, '「'.$testUser->email.'」を登録しました。');

        // テストユーザーを検索
        $this->search_user($testUser->email);

        // テストユーザーを凍結
        $this->invalid_user(1, '「'.$testUser->email.'」を無効にしました。');

        // テストユーザーを凍結解除
        $this->invalid_user(1, '「'.$testUser->email.'」を有効にしました。');

        // テストユーザーを削除
        $this->destroy_user($testUser, '1 件のデータを削除しました。');

        $testUser2 = new \stdClass();
        $testUser2->email = time();
        $testUser2->password = time();

        // テストユーザーを追加
        $this->store_user($testUser2, '「'.$testUser2->email.'」を登録しました。');

        // テストユーザーを検索
        $this->search_user($testUser2->email);

        // テストユーザーを更新
        $this->update_user($testUser2, '「'.$testUser2->email.'」を更新しました。');

        // 1行目のユーザーを選択して削除
        $this->select_user(1, '1 件のデータを削除しました。');

        // 重複エラー
        $this->store_user($testUser2, 'このユーザーIDはすでに登録されています。');
    }
}
