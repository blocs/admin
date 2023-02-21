<?php

namespace App\Admin\Controllers;

use Illuminate\Support\Facades\Hash;

trait UserEditTrait
{
    protected function outputEdit()
    {
        // 役割をメニューにセット
        $roleList = config('role');
        empty($roleList) || $this->addOption('role', array_keys($roleList));

        return parent::outputEdit();
    }

    protected function validateUpdate()
    {
        parent::validateUpdate();

        if (empty($this->request->password_new)) {
            return;
        }

        // 旧パスワードをチェック
        $user = call_user_func($this->mainTable.'::find', $this->val['id']);
        if ('' === $user->password) {
            return;
        }

        if (empty($this->request->password_old)) {
            return $this->backEdit('', 'パスワードが違います。', 'password_old');
        }

        if (!Hash::check($this->request->password_old, $user->password)) {
            return $this->backEdit('', 'パスワードが違います。', 'password_old');
        }
    }

    protected function prepareUpdateTrait()
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['role'] = empty($this->request->role) ? '' : implode("\t", $this->request->role);

        $requestData = [
            'name' => $this->val['name'],
            'role' => $this->val['role'],
        ];
        empty($this->request->password_new) || $requestData['password'] = Hash::make($this->request->password_new);

        return $requestData;
    }
}
