<?php

namespace App\Admin\Controllers;

use Illuminate\Support\Facades\Hash;

class UserController extends \Blocs\Controllers\Base
{
    use UserUpdateTrait;

    public function __construct()
    {
        define('BLOCS_AUTOINCLUDE_DIR', 'admin');
        parent::__construct();

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
}
