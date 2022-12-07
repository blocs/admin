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
        $this->noticeItem = 'email';
        $this->paginateNum = 20;
    }

    protected function prepareIndexSearch(&$mainTable)
    {
        foreach ($this->search_items as $search_item) {
            $mainTable->where(function ($query) use ($search_item) {
                $query
                    ->where('name', 'LIKE', '%'.$search_item.'%')
                    ->orWhere('email', 'LIKE', '%'.$search_item.'%');
            });
        }

        $mainTable->orderBy('email', 'asc');
    }

    protected function outputEntry()
    {
        $groups = config('group');
        empty($groups) || $this->add_option('group', array_keys($groups));

        return parent::outputEntry();
    }

    protected function executeInsert($request_data = [])
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['group'] = empty($this->request->group) ? '' : implode("\t", $this->request->group);

        $request_data = [
            'email' => $this->request->email,
            'name' => $this->val['name'],
            'password' => Hash::make($this->request->password),
            'group' => $this->val['group'],
        ];

        parent::executeInsert($request_data);
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

    protected function executeUpdate($request_data = [])
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['group'] = empty($this->request->group) ? '' : implode("\t", $this->request->group);

        $request_data = [
            'name' => $this->val['name'],
            'group' => $this->val['group'],
        ];
        empty($this->request->password_new) || $request_data['password'] = Hash::make($this->request->password_new);

        if (!empty($this->request->file)) {
            // 画像ファイルの登録
            $request_data['file'] = $this->request->file;

            $files = json_decode($request_data['file'], true);
            $request_data['filename'] = $files[0]['filename'];
        }

        parent::executeUpdate($request_data);
    }
}
