<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Hash;

class UserController extends \Blocs\Controllers\Base
{
    public function __construct()
    {
        defined('VIEW_PREFIX') || define('VIEW_PREFIX', 'admin');
        defined('ROUTE_PREFIX') || define('ROUTE_PREFIX', 'user');
        $this->viewPrefix = VIEW_PREFIX.'.'.ROUTE_PREFIX;

        $this->mainTable = 'App\Models\Admin\User';
        defined('LOOP_ITEM') || define('LOOP_ITEM', 'users');
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

    protected function outputEntry()
    {
        $groupList = config('group');
        empty($groupList) || $this->addOption('group', array_keys($groupList));

        return parent::outputEntry();
    }

    protected function executeInsert($requestData = [])
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['group'] = empty($this->request->group) ? '' : implode("\t", $this->request->group);

        $requestData = [
            'email' => $this->request->email,
            'name' => $this->val['name'],
            'password' => Hash::make($this->request->password),
            'group' => $this->val['group'],
        ];

        parent::executeInsert($requestData);
    }

    protected function validateUpdate()
    {
        parent::validateUpdate();

        if (empty($this->request->password_new)) {
            return;
        }

        // 旧パスワードをチェック
        if (empty($this->request->password_old)) {
            return $this->backEntry('', 'パスワードが違います。', 'password_old');
        }

        $user = call_user_func($this->mainTable.'::find', $this->val['id']);
        if (!Hash::check($this->request->password_old, $user->password)) {
            return $this->backEntry('', 'パスワードが違います。', 'password_old');
        }
    }

    protected function executeUpdate($requestData = [])
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['group'] = empty($this->request->group) ? '' : implode("\t", $this->request->group);

        $requestData = [
            'name' => $this->val['name'],
            'group' => $this->val['group'],
        ];
        empty($this->request->password_new) || $requestData['password'] = Hash::make($this->request->password_new);

        if (!empty($this->request->file)) {
            // 画像ファイルの登録
            $requestData['file'] = $this->request->file;

            $fileList = json_decode($requestData['file'], true);
            $requestData['filename'] = $fileList[0]['filename'];
        }

        parent::executeUpdate($requestData);
    }
}
