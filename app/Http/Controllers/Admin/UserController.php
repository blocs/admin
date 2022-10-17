<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Hash;

class UserController extends \Blocs\Controllers\Maintenance
{
    public function __construct()
    {
        define('TEMPLATE_PREFIX', 'admin.user');
        define('ROUTE_PREFIX', 'user');
        define('TABLE_MAIN', '\App\User');

        $this->notice_item = 'email';
    }

    protected function prepare_list_search(&$table_main)
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

    protected function execute_insert($table_data = [])
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;

        $table_data = [
            'email' => $this->request->email,
            'name' => $this->val['name'],
            'password' => Hash::make($this->request->password),
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

        $user = call_user_func(TABLE_MAIN.'::find', $this->val['id']);
        if (!Hash::check($this->request->password_old, $user->password)) {
            return $this->back_entry('', 'パスワードが違います。', 'password_old');
        }
    }

    protected function execute_update($table_data = [])
    {
        // nameの補完
        $this->val['name'] = strlen($this->request->name) ? $this->request->name : $this->request->email;

        if (empty($this->request->password_new)) {
            $table_data = [
                'name' => $this->val['name'],
            ];
        } else {
            $table_data = [
                'name' => $this->val['name'],
                'password' => Hash::make($this->request->password_new),
            ];
        }

        parent::execute_update($table_data);
    }
}
