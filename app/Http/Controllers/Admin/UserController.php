<?php

namespace App\Http\Controllers\Admin;

class UserController extends \Blocs\Controllers\Base
{
    use UserUpdateTrait;

    public function __construct()
    {
        $this->viewPrefix = 'admin.user';
        $this->mainTable = 'App\Models\Admin\User';
        $this->loopItem = 'users';
        $this->paginateNum = 20;
        $this->noticeItem = 'email';

        // 役割をメニューにセット
        $roleList = config('role');
        empty($roleList) || addOption('role', array_keys($roleList));
    }

    protected function prepareIndexSearch(&$mainTable)
    {
        foreach ($this->searchItems as $searchItem) {
            $mainTable->where(function ($query) use ($searchItem) {
                $query
                    ->where('name', 'LIKE', '%'.$searchItem.'%')
                    ->orWhere('email', 'LIKE', '%'.$searchItem.'%')
                    ->orWhere('role', 'LIKE', '%'.$searchItem.'%');
            });
        }
        docs([
            '<search>があれば、<'.$this->loopItem.'>のnameを<search>で部分一致検索',
            '<search>があれば、<'.$this->loopItem.'>のemailを<search>で部分一致検索',
            '<search>があれば、<'.$this->loopItem.'>のroleを<search>で部分一致検索',
        ]);

        // ソート
        $this->keepItem('sort');

        // ソート条件の初期化とバリデーション
        if (empty($this->val['sort']) || ! is_array($this->val['sort'])) {
            $this->val['sort'] = [];
        } else {
            $this->val['sort'] = array_filter($this->val['sort'], 'strlen');
        }

        // ソート条件が空の場合、デフォルト値を設定
        if (empty($this->val['sort'])) {
            $this->val['sort'] = ['email' => 'asc'];
        }

        // 指定された条件でソート
        $allowedSortItems = ['email', 'role', 'created_at'];
        foreach ($allowedSortItems as $sortItem) {
            if (! empty($this->val['sort'][$sortItem])) {
                $mainTable->orderBy($sortItem, $this->val['sort'][$sortItem]);
            }
        }
        docs('指定された条件でソート');
    }

    protected function prepareStore()
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['role'] = empty($this->request->role) ? '' : implode("\t", $this->request->role);
        docs('<name>がなければ、<email>を指定する');

        return [
            'email' => $this->request->email,
            'name' => $this->val['name'],
            'role' => $this->val['role'],
            'password' => bcrypt($this->request->password),
        ];
    }
}
