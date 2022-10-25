<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Hash;

class UserController extends \Blocs\Controllers\Base
{
    public function __construct()
    {
        defined('ROUTE_PREFIX') || define('ROUTE_PREFIX', 'user');

        $this->template_prefix = 'admin.user';
        $this->table_main = '\App\User';
        $this->paginate_num = 20;
        $this->notice_item = 'email';
    }

    protected function prepare_index_search(&$table_main)
    {
        foreach ($this->search_items as $search_item) {
            $table_main->where(function ($query) use ($search_item) {
                $query
                    ->where('name', 'LIKE', '%'.$search_item.'%')
                    ->orWhere('email', 'LIKE', '%'.$search_item.'%');
            });
        }

        $table_main->orderBy('email', 'asc');
    }

    protected function output_entry()
    {
        $groups = config('group');
        empty($groups) || \Blocs\Option::add('group', array_keys($groups));

        return parent::output_entry();
    }

    protected function execute_insert($table_data = [])
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['group'] = empty($this->request->group) ? '' : implode("\t", $this->request->group);

        $table_data = [
            'email' => $this->request->email,
            'name' => $this->val['name'],
            'password' => Hash::make($this->request->password),
            'group' => $this->val['group'],
        ];

        parent::execute_insert($table_data);
    }

    protected function validate_update()
    {
        parent::validate_update();

        if (empty($this->request->password_new)) {
            return;
        }

        // 旧パスワードをチェック
        if (empty($this->request->password_old)) {
            return $this->back_entry('', 'パスワードが違います。', 'password_old');
        }

        $user = call_user_func($this->table_main.'::find', $this->val['id']);
        if (!Hash::check($this->request->password_old, $user->password)) {
            return $this->back_entry('', 'パスワードが違います。', 'password_old');
        }
    }

    protected function execute_update($table_data = [])
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;
        $this->val['group'] = empty($this->request->group) ? '' : implode("\t", $this->request->group);

        if (empty($this->request->password_new)) {
            $table_data = [
                'name' => $this->val['name'],
                'group' => $this->val['group'],
            ];
        } else {
            $table_data = [
                'name' => $this->val['name'],
                'password' => Hash::make($this->request->password_new),
                'group' => $this->val['group'],
            ];
        }

        parent::execute_update($table_data);
    }
}
