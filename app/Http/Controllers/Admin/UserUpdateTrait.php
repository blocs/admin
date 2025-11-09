<?php

namespace App\Http\Controllers\Admin;

trait UserUpdateTrait
{
    protected function validateUpdate()
    {
        parent::validateUpdate();

        if ($this->shouldSkipPasswordValidation()) {
            return;
        }

        // 現在のパスワード入力の妥当性を確認する
        if ($this->isCurrentPasswordMissing()) {
            docs('現在のパスワードが空なら、間違いを知らせて編集画面に戻す');

            return $this->backEdit('', __('template:admin_profile_password_incorrect'), 'password_old');
        }

        if ($this->isCurrentPasswordInvalid()) {
            docs('入力された現在のパスワードが違えば、同じく編集画面に戻す');

            return $this->backEdit('', __('template:admin_profile_password_incorrect'), 'password_old');
        }
        docs(['POST' => 'password_old', 'データベース' => $this->loopItem], '<password_old>を受け取ったら、保存している<'.$this->loopItem.'>のパスワードと比べる');
        docs(null, "<password_old>と保存済みのパスワードが違えば、同じ知らせを出して編集画面に戻す\n・".__('template:admin_profile_password_incorrect'), ['FORWARD' => '!'.prefix().'.edit']);
    }

    protected function prepareUpdate()
    {
        $requestData = [
            'email' => $this->request->email,
        ];

        // nameが未入力の場合はemailを利用して補完する
        if ($this->request->has('name')) {
            $this->applyNameFallback($requestData);
        }

        if ($this->request->has('role')) {
            $this->applyRoleAggregation($requestData);
        }

        $this->applyPasswordRenewal($requestData);

        $this->prepareUpdateTrait($requestData);

        return $requestData;
    }

    private function shouldSkipPasswordValidation(): bool
    {
        return $this->isPasswordUpdateEmpty() || $this->isStoredPasswordBlank();
    }

    private function isPasswordUpdateEmpty(): bool
    {
        return empty($this->request->password_new);
    }

    private function isStoredPasswordBlank(): bool
    {
        return $this->tableData->password === '';
    }

    private function isCurrentPasswordMissing(): bool
    {
        return empty($this->request->password_old);
    }

    private function isCurrentPasswordInvalid(): bool
    {
        return ! password_verify($this->request->password_old, $this->tableData->password);
    }

    private function applyNameFallback(array &$requestData): void
    {
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $requestData['name'] = $this->val['name'];
        docs('<name>が空なら、かわりに<email>を入れる');
    }

    private function applyRoleAggregation(array &$requestData): void
    {
        $this->val['role'] = empty($this->request->role) ? '' : implode("\t", $this->request->role);
        $requestData['role'] = $this->val['role'];
        docs('<role>の入力は、タブで区切った一つの文字列にまとめる');
    }

    private function applyPasswordRenewal(array &$requestData): void
    {
        if ($this->isPasswordUpdateEmpty()) {
            return;
        }

        $requestData['password'] = bcrypt($this->request->password_new);
        docs('新しいパスワードが入力されたら、暗号化して保存する');
    }

    protected function prepareUpdateTrait(&$requestData) {}
}
