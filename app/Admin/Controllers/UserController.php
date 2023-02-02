<?php

namespace App\Admin\Controllers;

use Illuminate\Support\Facades\Hash;

class UserController extends \Blocs\Controllers\Base
{
    public function __construct()
    {
        define('ROUTE_PREFIX', 'user');

        $this->viewPrefix = VIEW_PREFIX.'.'.ROUTE_PREFIX;
        $this->mainTable = 'App\Models\Admin\User';
        $this->loopItem = 'users';
        $this->paginateNum = 20;
        $this->noticeItem = 'email';
        $this->menuName = 'admin';
    }

    protected function prepareIndexSearch(&$mainTable)
    {
        foreach ($this->searchItems as $searchItem) {
            $mainTable->where(function ($query) use ($searchItem) {
                $query
                    ->where('name', 'LIKE', '%'.$searchItem.'%')
                    ->orWhere('email', 'LIKE', '%'.$searchItem.'%');
            });
        }

        $mainTable->orderBy('email', 'asc');
    }

    protected function outputCreate()
    {
        // 役割をメニューにセット
        $roleList = config('role');
        empty($roleList) || $this->addOption('role', array_keys($roleList));

        return parent::outputCreate();
    }

    protected function prepareStore()
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['role'] = empty($this->request->role) ? '' : implode("\t", $this->request->role);

        return [
            'email' => $this->request->email,
            'name' => $this->val['name'],
            'password' => Hash::make($this->request->password),
            'role' => $this->val['role'],
        ];
    }

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
        if (empty($this->request->password_old)) {
            return $this->backEdit('', 'パスワードが違います。', 'password_old');
        }

        $user = call_user_func($this->mainTable.'::find', $this->val['id']);
        if (!Hash::check($this->request->password_old, $user->password)) {
            return $this->backEdit('', 'パスワードが違います。', 'password_old');
        }
    }

    protected function prepareUpdate()
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
