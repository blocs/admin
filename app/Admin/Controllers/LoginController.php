<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use \Blocs\Auth\AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = ADMIN_LOGIN_REDIRECT_TO;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');

        $GLOBALS['BLOCS_AUTOINCLUDE_DIR'] = 'admin';
        $this->viewPrefix = ADMIN_VIEW_PREFIX.'.auth';
    }

    public function showLoginForm()
    {
        return view($this->viewPrefix.'.login');
    }

    protected function loggedOut(Request $request)
    {
        return redirect(ADMIN_LOGOUT_REDIRECT_TO);
    }

    protected function validateLogin(Request $request)
    {
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.login');
        empty($rules) || $request->validate($rules, $messages);
    }

    protected function credentials(Request $request)
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['disabled_at' => null]
        );
    }
}
