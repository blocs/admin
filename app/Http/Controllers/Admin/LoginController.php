<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use \Blocs\Auth\AuthenticatesUsers;
    use \Blocs\Controllers\CommonTrait;

    protected $viewPrefix;

    // ログイン成功後のリダイレクト先URL
    protected $redirectTo;

    public function __construct()
    {
        $this->viewPrefix = 'admin.auth';

        // ログイン成功後の遷移先を設定
        $this->redirectTo = '/home';
    }

    public function showLoginForm()
    {
        // 既にログイン済みの場合は、ホーム画面へリダイレクト
        if ($this->guard()->check()) {
            return redirect($this->redirectTo);
        }

        // ログイン画面のビューを生成
        $view = view($this->viewPrefix.'.login');
        docs('テンプレートを読み込んで、ログイン画面のHTMLを生成');

        return $view;
    }

    protected function loggedOut(Request $request)
    {
        // ログアウト後は、ログイン画面へリダイレクト
        return redirect('/login');
    }

    protected function validateLogin(Request $request)
    {
        // バリデーションルールとメッセージを取得
        [$rules, $messages] = $this->getLoginValidationRules($request);

        // バリデーションルールが存在しない場合は処理をスキップ
        if (empty($rules)) {
            return;
        }

        // ラベルとバリデーション情報を取得してリクエストを検証
        $labels = $this->getLoginFormLabels();
        $request->validate($rules, $messages, $labels);
        $validates = $this->getValidate($rules, $messages, $labels);

        docs(['POST' => '入力値'], '入力値を以下の条件で検証して、エラーがあればメッセージをセット', null, $validates);
        docs(null, 'エラーがあれば、ログイン画面に戻る', ['FORWARD' => '!'.$this->viewPrefix.'.login']);
    }

    protected function credentials(Request $request)
    {
        // ログイン認証に使用する認証情報を取得
        // ユーザー名・パスワードに加えて、削除済み・無効化済みのユーザーを除外
        return array_merge(
            $request->only($this->username(), 'password'),
            ['deleted_at' => null, 'disabled_at' => null]
        );
    }

    protected function guard()
    {
        // 認証に使用するガードインスタンスを取得
        return Auth::guard();
    }

    // ログインフォームのバリデーションルールとメッセージを取得
    private function getLoginValidationRules(Request $request)
    {
        return \Blocs\Validate::get($this->viewPrefix.'.login', $request);
    }

    // ログインフォームの項目ラベルを取得
    private function getLoginFormLabels()
    {
        return $this->getLabel($this->viewPrefix.'.login');
    }
}
