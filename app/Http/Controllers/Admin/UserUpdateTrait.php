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
            return $this->backEdit('', lang('template:admin_profile_password_incorrect'), 'password_old');
        }

        if ($this->isCurrentPasswordInvalid()) {
            return $this->backEdit('', lang('template:admin_profile_password_incorrect'), 'password_old');
        }
        docs(['POST' => 'password_old', 'データベース' => $this->loopItem], '<password_old>があれば、<'.$this->loopItem.'>をチェック');
        docs(null, "<password_old>が一致しなければ、メッセージをセットして編集画面に戻る\n・".lang('template:admin_profile_password_incorrect'), ['FORWARD' => '!'.prefix().'.edit']);
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
        docs('<name>がなければ、<email>を指定する');
    }

    private function applyRoleAggregation(array &$requestData): void
    {
        $this->val['role'] = empty($this->request->role) ? '' : implode("\t", $this->request->role);
        $requestData['role'] = $this->val['role'];
    }

    private function applyPasswordRenewal(array &$requestData): void
    {
        if ($this->isPasswordUpdateEmpty()) {
            return;
        }

        $requestData['password'] = bcrypt($this->request->password_new);
    }

    protected function prepareUpdateTrait(&$requestData) {}
}
