<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Agent Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use \Blocs\Auth\AuthenticatesUsers;
    use \Blocs\Controllers\CommonTrait;
    use \Blocs\Agent\CommonTrait;
    use \Blocs\Agent\AgentTrait;
    use AgentToolsTrait;

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
        $this->agent = 'agent';
    }

    public function showAgentForm()
    {
        if (!$this->getError()) {
            $response = $this->guessTool();
            if (is_object($response)) {
                return $response;
            }
        }

        $view = view($this->viewPrefix.'.agent', $this->val);

        return $view;
    }

    protected function loggedOut(Request $request)
    {
        // ログアウト後の遷移先
        return redirect('/');
    }

    protected function validateLogin(Request $request)
    {
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.agent', $request);
        if (empty($rules)) {
            return;
        }

        $labels = $this->getLabel($this->viewPrefix.'.agent');
        $request->validate($rules, $messages, $labels);
        $validates = $this->getValidate($rules, $messages, $labels);

        docs(['POST' => '入力値'], '入力値を以下の条件で検証して、エラーがあればメッセージをセット', null, $validates);
        docs(null, 'エラーがあれば、ログイン画面に戻る', ['FORWARD' => '!'.$this->viewPrefix.'.agent']);
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
