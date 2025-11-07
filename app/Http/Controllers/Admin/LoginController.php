<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use \Blocs\Auth\AuthenticatesUsers;
    use \Blocs\Controllers\CommonTrait;

    protected string $viewPrefix = 'admin.auth';

    // ログイン成功後に誘導するURL
    protected string $redirectTo = '/home';

    public function showLoginForm()
    {
        // 既に認証済みならホーム画面へリダイレクトして多重ログインを防止
        if ($this->guard()->check()) {
            return redirect($this->redirectTo);
        }

        // ログイン画面のビューを生成し、表示用データを組み立て
        $loginView = view($this->viewPrefix.'.login');
        docs('テンプレートを読み込んで、ログイン画面のHTMLを生成');

        return $loginView;
    }

    protected function loggedOut(Request $request)
    {
        // ログアウト後はログイン画面に戻し、再ログインを促す
        return redirect('/login');
    }

    protected function validateLogin(Request $request)
    {
        // バリデーションルールとメッセージを取得し、入力チェックを準備
        [$rules, $messages] = $this->fetchLoginValidationConfig($request);

        // バリデーションルールが存在しない場合は処理をスキップ
        if (empty($rules)) {
            return;
        }

        // ラベルとバリデーション情報を取得してリクエストを検証
        $labels = $this->fetchLoginFormLabels();
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
        // 認証に使用するガードインスタンスを取得し、共通処理に委ねる
        return Auth::guard();
    }

    // ログインフォームのバリデーションルールとメッセージを取得
    private function fetchLoginValidationConfig(Request $request)
    {
        return \Blocs\Validate::get($this->viewPrefix.'.login', $request);
    }

    // ログインフォームの項目ラベルを取得
    private function fetchLoginFormLabels()
    {
        return $this->getLabel($this->viewPrefix.'.login');
    }
}
