<?php

namespace App\Admin\Controllers;

trait UserUpdateTrait
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

        if ('' === $this->tableData->password) {
            return;
        }

        // 現パスワードをチェック
        if (empty($this->request->password_old)) {
            return $this->backEdit('', 'パスワードが違います。', 'password_old');
        }

        if (!password_verify($this->request->password_old, $this->tableData->password)) {
            return $this->backEdit('', 'パスワードが違います。', 'password_old');
        }
        doc(['POST' => 'password_old', 'データベース' => $this->loopItem], '<password_old>があれば、<'.$this->loopItem.'>をチェック');
        doc(null, "<password_old>が一致しなければ、メッセージをセットして編集画面に戻る\n・".'パスワードが違います。', ['FORWARD' => prefix().'.edit']);
    }

    protected function prepareUpdate()
    {
        $requestData = [];

        // nameの補完
        if ($this->request->has('name')) {
            $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
            $requestData['name'] = $this->val['name'];
        }
        doc('<name>がなければ、<email>を指定する');

        if ($this->request->has('role')) {
            $this->val['role'] = empty($this->request->role) ? '' : implode("\t", $this->request->role);
            $requestData['role'] = $this->val['role'];
        }

        empty($this->request->password_new) || $requestData['password'] = bcrypt($this->request->password_new);

        $this->prepareUpdateTrait($requestData);

        return $requestData;
    }

    protected function prepareUpdateTrait(&$requestData)
    {
    }
}
