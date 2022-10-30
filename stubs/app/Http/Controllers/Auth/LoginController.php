<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
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

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');

        $this->template_prefix = 'admin';
    }

    public function showLoginForm()
    {
        return view($this->template_prefix.'.auth.login');
    }

    protected function loggedOut(Request $request)
    {
        return redirect('/login');
    }

    protected function validateLogin(Request $request)
    {
        list($validate, $message) = \Blocs\Validate::get($this->template_prefix.'.auth.login');
        empty($validate) || $request->validate($validate, $message);
    }

    protected function credentials(Request $request)
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['disabled_at' => null]
        );
    }
}
