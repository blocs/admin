<?php

namespace App\Admin\Controllers;

class UserController extends \Blocs\Controllers\Base
{
    use UserUpdateTrait;

    public function __construct()
    {
        $this->setAutoinclude(resource_path('views/admin/autoinclude'));
        $this->viewPrefix = ADMIN_VIEW_PREFIX.'.user';
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
                    ->orWhere('email', 'LIKE', '%'.$searchItem.'%')
                    ->orWhere('role', 'LIKE', '%'.$searchItem.'%');
            });
        }
        doc([
            '<search>があれば、<'.$this->loopItem.'>のnameを<search>で部分一致検索',
            '<search>があれば、<'.$this->loopItem.'>のemailを<search>で部分一致検索',
            '<search>があれば、<'.$this->loopItem.'>のroleを<search>で部分一致検索',
        ]);

        $mainTable->orderBy('email', 'asc');
        doc('emailで昇順にソート');
    }

    protected function prepareIndex()
    {
        parent::prepareIndex();

        foreach ($this->val[$this->loopItem] as $loopKey => $loopValue) {
            $roleList = explode("\t", $loopValue['role']);
            $this->val[$this->loopItem][$loopKey]['roles'] = $roleList;
        }
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
        doc('<name>がなければ、<email>を指定する');

        return [
            'email' => $this->request->email,
            'name' => $this->val['name'],
            'password' => bcrypt($this->request->password),
            'role' => $this->val['role'],
        ];
    }
}
