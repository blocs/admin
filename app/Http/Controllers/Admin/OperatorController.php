<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Operator Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use \Blocs\Auth\AuthenticatesUsers;
    use \Blocs\Controllers\CommonTrait;
    use OperatorTrait;
    use OperatorToolsTrait;

    protected $viewPrefix;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo;
    private $val = [];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // ログイン後の遷移先
        $this->redirectTo = '/';

        $this->viewPrefix = 'admin.auth';
    }

    public function showOperatorForm()
    {
        if (!$this->getError()) {
            $response = $this->findTool();
            if (is_object($response)) {
                return $response;
            }
        }

        $view = view($this->viewPrefix.'.operator', $this->val);

        return $view;
    }

    protected function loggedOut(Request $request)
    {
        // ログアウト後の遷移先
        return redirect('/');
    }

    protected function validateLogin(Request $request)
    {
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.operator', $request);
        if (empty($rules)) {
            return;
        }

        $labels = $this->getLabel($this->viewPrefix.'.operator');
        $request->validate($rules, $messages, $labels);
        $validates = $this->getValidate($rules, $messages, $labels);

        docs(['POST' => '入力値'], '入力値を以下の条件で検証して、エラーがあればメッセージをセット', null, $validates);
        docs(null, 'エラーがあれば、ログイン画面に戻る', ['FORWARD' => '!'.$this->viewPrefix.'.operator']);
    }

    protected function credentials(Request $request)
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['deleted_at' => null, 'disabled_at' => null]
        );
    }

    protected function guard()
    {
        return Auth::guard();
    }
}
