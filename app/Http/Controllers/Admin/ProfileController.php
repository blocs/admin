<?php

namespace App\Http\Controllers\Admin;

use Blocs\Controllers\Base;
use Blocs\Menu;
use Illuminate\Http\Request;
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

    public function update($id, Request $request)
    {
        // プロフィール更新も自分自身のレコードだけを対象とする
        if ((string) $id !== (string) Auth::id()) {
            abort(403);
        }

        return parent::update($id, $request);
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
        unset($requestData['role']);
        docs('役割は変更させないため、送信されたデータからroleを外す');

        // プロフィール画像は、送信されたときだけ保存値を入れ替える（空文字などが送信された場合は既存画像を削除）
        if ($this->request->has('file')) {
            $filePayload = $this->request->file;
            $this->abortIfProfileFileNameIsUnsafe($filePayload);
            $requestData['file'] = empty($filePayload) ? null : $filePayload;
        }
        docs('プロフィール画像の入力内容を確認して保存する値を整える');
    }

    /**
     * 画像の入力値（ファイル情報のJSON）に、ディレクトリをさかのぼるファイル名が無いことを確かめる。
     * 入力値は1件のファイル情報、またはその一覧のどちらでも受け付ける。
     */
    private function abortIfProfileFileNameIsUnsafe(mixed $filePayload): void
    {
        $decoded = is_string($filePayload) ? json_decode($filePayload, true) : $filePayload;
        if (! is_array($decoded)) {
            return;
        }

        foreach (isset($decoded['filename']) ? [$decoded] : $decoded as $file) {
            $filename = is_array($file) ? ($file['filename'] ?? null) : null;
            if (! is_string($filename)) {
                continue;
            }

            $normalized = str_replace('\\', '/', $filename);
            if ($normalized !== basename($normalized) || str_contains($normalized, '..')) {
                abort(403);
            }
        }
    }
}
