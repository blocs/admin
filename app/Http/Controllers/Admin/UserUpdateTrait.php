<?php

namespace App\Http\Controllers\Admin;

trait UserUpdateTrait
{
    protected function validateUpdate()
    {
        parent::validateUpdate();

        // プロフィールでパスワードを変えるときだけ、現在のパスワードを確認する
        if (empty($this->request->password_new) || $this->viewPrefix !== 'admin.profile') {
            return;
        }

        // 現在のパスワード入力の妥当性を確認する
        $currentPassword = $this->request->password_old;
        if (empty($currentPassword) || ! is_string($currentPassword)) {
            docs('現在のパスワードが空なら、間違いを知らせて編集画面に戻す');

            return $this->backEdit('', lang('template:admin_profile_password_incorrect'), 'password_old');
        }

        if (! password_verify($currentPassword, $this->tableData->password)) {
            docs('入力された現在のパスワードが違えば、同じく編集画面に戻す');

            return $this->backEdit('', lang('template:admin_profile_password_incorrect'), 'password_old');
        }

        docs(['POST' => 'password_old', 'データベース' => $this->loopItem], '<password_old>を受け取ったら、保存している<'.$this->loopItem.'>のパスワードと比べる');
        docs(null, "<password_old>と保存済みのパスワードが違えば、同じ知らせを出して編集画面に戻す\n・".lang('template:admin_profile_password_incorrect'), ['FORWARD' => '!'.prefix().'.edit']);
    }

    protected function prepareUpdate()
    {
        $requestData = [
            'email' => $this->request->email,
        ];

        if ($this->request->has('name')) {
            $requestData['name'] = $this->val['name'] = $this->resolveUserName();
        }

        if ($this->request->has('role')) {
            $requestData['role'] = $this->val['role'] = $this->aggregateRoles();
        }

        if (! empty($this->request->password_new) && is_string($this->request->password_new)) {
            $requestData['password'] = bcrypt($this->request->password_new);
            docs('新しいパスワードが入力されたら、暗号化して保存する');
        }

        $this->prepareUpdateTrait($requestData);

        return $requestData;
    }

    protected function prepareUpdateTrait(&$requestData) {}

    /**
     * nameが未入力の場合はemailを利用して補完する
     */
    private function resolveUserName(): ?string
    {
        docs('<name>が空なら、かわりに<email>を入れる');

        return strlen((string) $this->request->name) ? $this->request->name : $this->request->email;
    }

    /**
     * 選択された役割を、設定ファイルにあるものだけに絞ってタブ区切りの文字列にまとめる
     */
    private function aggregateRoles(): string
    {
        docs('<role>の入力は、タブで区切った一つの文字列にまとめる');

        $selectedRoles = array_filter((array) $this->request->role, 'is_string');

        // 役割が設定ファイルに定義されていない場合は絞り込まない
        $definedRoles = array_keys(config('role') ?? []);

        return implode("\t", empty($definedRoles) ? $selectedRoles : array_intersect($selectedRoles, $definedRoles));
    }
}
