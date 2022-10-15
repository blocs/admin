<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    private $val = [];

    public function __construct()
    {
        define('TEMPLATE_PREFIX', 'admin.profile');

        $this->middleware('auth');
    }

    public function entry()
    {
        $this->prepare_entry();

        return $this->output_entry();
    }

    protected function prepare_entry()
    {
        $_user_data = \Auth::user();

        empty(old('email')) && $this->val['email'] = $_user_data['email'];
        empty(old('name')) && $this->val['name'] = $_user_data['name'];
    }

    protected function output_entry()
    {
        $this->val = array_merge($this->val, \Blocs\Notice::get());

        return view(TEMPLATE_PREFIX.'.entry', $this->val);
    }

    protected function submit(Request $request)
    {
        list($validate, $message) = \Blocs\Validate::get(TEMPLATE_PREFIX.'.entry');
        empty($validate) || $request->validate($validate, $message);

        $_user_data = \Auth::user();

        $user = User::find($_user_data->id);

        // nameの編集
        strlen($request->name) || $request->name = $_user_data->email;
        $user->name = $request->name;

        if (!empty($request->password_new)) {
            // パスワードを変更する
            if (empty($request->password_old) || !Hash::check($request->password_old, $_user_data->password)) {
                // 旧パスワードが間違っている
                return redirect()->route('profile')
                    ->withInput()
                    ->withErrors(['password_old' => 'パスワードが違います。']);
            }

            $user->password = Hash::make($request->password_new);
        }

        $user->save();

        return $this->output_submit();
    }

    protected function output_submit()
    {
        \Blocs\Notice::set('success', 'admin_profile_updated');

        return redirect()->route('home');
    }
}
