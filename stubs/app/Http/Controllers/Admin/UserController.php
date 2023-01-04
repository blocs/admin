<?php

namespace App\Http\Controllers\Admin;

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
        // グループをメニューにセット
        $groupList = config('group');
        empty($groupList) || $this->addOption('group', array_keys($groupList));

        return parent::outputCreate();
    }

    protected function prepareStore()
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['group'] = empty($this->request->group) ? '' : implode("\t", $this->request->group);

        return [
            'email' => $this->request->email,
            'name' => $this->val['name'],
            'password' => Hash::make($this->request->password),
            'group' => $this->val['group'],
        ];
    }

    protected function outputEdit()
    {
        // グループをメニューにセット
        $groupList = config('group');
        empty($groupList) || $this->addOption('group', array_keys($groupList));

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
        $this->val['group'] = empty($this->request->group) ? '' : implode("\t", $this->request->group);

        $requestData = [
            'name' => $this->val['name'],
            'group' => $this->val['group'],
        ];
        empty($this->request->password_new) || $requestData['password'] = Hash::make($this->request->password_new);

        return $requestData;
    }
}
