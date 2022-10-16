<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    private $val = [];
    private $request;

    public function __construct()
    {
        define('TEMPLATE_PREFIX', 'admin.profile');
        define('TABLE_MAIN', '\App\User');
    }

    public function entry()
    {
        if (empty(old())) {
            $this->get_current();
        }

        $this->val = array_merge($this->val, \Blocs\Notice::get());

        $this->prepare_entry();

        return $this->output_entry();
    }

    protected function get_current()
    {
        $table_data = \Auth::user();

        $this->val = array_merge($table_data->toArray(), $this->val);
    }

    protected function prepare_entry()
    {
    }

    protected function output_entry()
    {
        return view(TEMPLATE_PREFIX.'.update', $this->val);
    }

    public function update(Request $request)
    {
        $this->request = $request;

        $this->validate_update();
        $this->prepare_update();
        $this->register_update();

        return $this->output_update();
    }

    protected function validate_update()
    {
        list($validate, $message) = \Blocs\Validate::get(TEMPLATE_PREFIX.'.update');
        empty($validate) || $this->request->validate($validate, $message);
    }

    protected function prepare_update()
    {
        // nameの編集
        strlen($this->request->name) || $this->request->name = $this->request->email;
    }

    protected function register_update()
    {
        $auth_user = \Auth::user();
        $user = call_user_func(TABLE_MAIN.'::find', $auth_user->id);

        $user->name = $this->request->name;

        if (!empty($this->request->password_new)) {
            // パスワードを変更する
            if (empty($this->request->password_old) || !Hash::check($this->request->password_old, $user->password)) {
                // 旧パスワードが間違っている
                return redirect()->route('profile.entry')
                    ->withInput()
                    ->withErrors(['password_old' => 'パスワードが違います。']);
            }

            $user->password = Hash::make($this->request->password_new);
        }

        $user->save();
    }

    protected function output_update()
    {
        \Blocs\Notice::set('success', 'admin_profile_updated');

        return redirect()->route('home');
    }
}
