<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Blocs\Auth\AuthenticatesUsers;
use Blocs\Controllers\CommonTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;
    use CommonTrait;

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
        docs('画面のテンプレートを読み込み、ログイン画面のHTMLを生成する');

        return $loginView;
    }

    protected function loggedOut(Request $request)
    {
        // ログアウト後はログイン画面に戻し、再ログインを促す
        return redirect('/login');
    }

    protected function validateLogin(Request $request)
    {
        // テンプレートのバリデーションルールとメッセージ、ラベルを使ってリクエストを検証（ルールが無ければスキップ）
        $validated = $this->validateWithTemplate(
            $request,
            $this->viewPrefix.'.login',
            ['POST' => '入力値'],
            '入力値を以下の条件で検証して、エラーがあればメッセージをセット'
        );
        if ($validated) {
            docs(null, '入力に問題があればログイン画面へ戻してやり直してもらう', ['FORWARD' => '!'.$this->viewPrefix.'.login']);
        }

        // 配列が送られても認証処理でエラーにならないよう、入力エラーとして扱う
        $rejectArray = function (string $attribute, mixed $value, \Closure $fail): void {
            is_array($value) && $fail(trans('auth.failed'));
        };
        $request->validate([
            $this->username() => $rejectArray,
            'password' => $rejectArray,
        ]);
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
}
