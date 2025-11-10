<?php

namespace App\Http\Controllers\Admin;

use Blocs\Controllers\Base;
use Blocs\Menu;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Base
{
    use UserUpdateTrait;

    public function __construct()
    {
        $this->viewPrefix = 'admin.profile';
        $this->mainTable = 'App\Models\Admin\User';
        $this->loopItem = 'users';
        $this->noticeItem = 'email';

        // プロフィールメニューのヘッドライン表示を初期化
        Menu::headline('fa-cog', lang('template:admin_menu_profile'));

        // プロフィール関連ナビゲーションをアクティブ表示にするフラグ
        $this->val['profileActive'] = true;
    }

    public function edit($id)
    {
        // プロフィール編集は常にログインユーザー自身を対象とする
        return parent::edit(Auth::id());
    }

    protected function outputUpdate()
    {
        docs('ホーム画面に戻して更新が成功したことをお知らせする');

        // 更新完了後は一時的に保持していた状態を初期化
        unset($this->val, $this->request, $this->tableData);

        // ホーム画面へ遷移し、更新成功メッセージを通知
        return redirect()->route('home')->with([
            'category' => 'success',
            'message' => lang('success:admin_profile_updated'),
        ]);
    }

    protected function prepareUpdateTrait(&$requestData)
    {
        // プロフィール更新では役割の更新を許可しないため入力値から除外する
        $this->applyRoleFieldFilter($requestData);
        docs('役割は変更させないため、送信されたデータからroleを外す');

        // プロフィール画像の送信内容に応じて保存値を整形する
        $this->applyProfileImageAdjustment($requestData);
        docs('プロフィール画像の入力内容を確認して保存する値を整える');
    }

    private function applyRoleFieldFilter(array &$requestData): void
    {
        // 役割は固定のため、入力データからroleを除外する
        unset($requestData['role']);
    }

    private function applyProfileImageAdjustment(array &$requestData): void
    {
        // 画像入力が送信されていない場合は処理なし
        if (! $this->request->has('file')) {
            return;
        }

        if (empty($this->request->file)) {
            // 空文字などが送信された場合は既存画像を削除する
            $requestData['file'] = null;

            return;
        }

        // 新しい画像が送信された場合はその値を反映する
        $requestData['file'] = $this->request->file;
    }
}
