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
    }

    protected function prepare_list_search(&$table_main)
    {
        if (empty($this->search_items)) {
            return;
        }

        foreach ($this->search_items as $search_item) {
            $table_main->where(function ($query) use ($search_item) {
                $query->orWhere('name', 'like', '%'.$search_item.'%')
                  ->orWhere('email', 'LIKE', '%'.$search_item.'%');
            });
        }
    }

    protected function execute_insert($table_data = [])
    {
        // nameの編集
        strlen($this->request->name) || $this->request->name = $this->request->email;

        parent::execute_insert([
            'email' => $this->request->email,
            'name' => $this->request->name,
            'password' => Hash::make($this->request->password),
        ]);
    }

    protected function validate_update()
    {
        parent::validate_update();

        if (empty($this->request->password_new)) {
            return;
        }

        // 旧パスワードをチェック
        if (empty($this->request->password_old)) {
            return $this->back_entry('password_old', '', 'パスワードが違います。');
        }

        $user = call_user_func(TABLE_MAIN.'::find', $this->val['id']);
        if (!Hash::check($this->request->password_old, $user->password)) {
            return $this->back_entry('password_old', '', 'パスワードが違います。');
        }
    }

    protected function execute_update($table_data = [])
    {
        // nameの編集
        strlen($this->request->name) || $this->request->name = $this->request->email;

        parent::execute_update([
            'name' => $this->request->name,
            'password' => Hash::make($this->request->password_new),
        ]);
    }
}
