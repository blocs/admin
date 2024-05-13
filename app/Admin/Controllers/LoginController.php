<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Blocs\Middleware\RedirectIfAuthenticated;
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
    use \Blocs\Controllers\CommonTrait;

    protected $val = [];
    protected $tableData;
    protected $mainTable;
    protected $viewPrefix;

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
        $this->middleware(RedirectIfAuthenticated::class)->except('logout');

        $this->viewPrefix = ADMIN_VIEW_PREFIX.'.auth';
    }

    public function showLoginForm()
    {
        $view = view($this->viewPrefix.'.login');
        unset($this->val, $this->request, $this->tableData);
        doc('テンプレートを読み込んで、HTMLを生成');

        return $view;
    }

    protected function loggedOut(Request $request)
    {
        unset($this->val, $this->request, $this->tableData);

        return redirect(ADMIN_LOGOUT_REDIRECT_TO);
    }

    protected function validateLogin(Request $request)
    {
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.login');
        if (!empty($rules)) {
            $labels = $this->getLabel($this->viewPrefix.'.login');
            $request->validate($rules, $messages, $labels);
            $validates = $this->getValidate($rules, $messages, $labels);
            doc(['POST' => '入力値'], '入力値を以下の条件で検証して、エラーがあればメッセージをセット', null, $validates);
            doc(null, 'エラーがあれば、ログイン画面に戻る', ['FORWARD' => $this->viewPrefix.'.login']);
        }
    }

    protected function credentials(Request $request)
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['deleted_at' => null, 'disabled_at' => null]
        );
    }
}
