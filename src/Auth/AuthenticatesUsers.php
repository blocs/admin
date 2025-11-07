<?php

namespace Blocs\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait AuthenticatesUsers
{
    use RedirectsUsers;
    use ThrottlesLogins;

    /**
     * ログインフォームのビューを返す。
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * ログインリクエストを検証し、認証結果に応じたレスポンスを返す。
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|JsonResponse
     *
     * @throws ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // ThrottlesLogins トレイトが有効な場合は、過剰なログイン試行を直ちに遮断
        if ($this->shouldThrottleLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            $this->storePasswordConfirmationTimestamp($request);

            return $this->sendLoginResponse($request);
        }

        // 認証失敗時は試行回数を記録し、エラー応答を返却
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * ログイン入力値をバリデーションする。
     *
     * @return void
     *
     * @throws ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * ガードを通じて認証を試行する。
     *
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt(
            $this->credentials($request), $request->boolean('remember')
        );
    }

    /**
     * リクエストから認証に利用する資格情報を抽出する。
     *
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    /**
     * 認証成功後のセッション更新とレスポンス生成を行う。
     *
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return $request->wantsJson()
                    ? new JsonResponse([], 204)
                    : redirect()->intended($this->redirectPath());
    }

    /**
     * 認証完了後に任意処理を差し込むためのフック。
     */
    protected function authenticated(Request $request, $user) {}

    /**
     * 認証失敗時のレスポンスを生成する。
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([$this->username() => [trans('auth.failed')]]);
    }

    /**
     * ログインに使用するユーザー名のキーを返す。
     *
     * @return string
     */
    public function username()
    {
        return 'email';
    }

    /**
     * ログアウト処理を実行し、遷移先レスポンスを返す。
     *
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect('/');
    }

    /**
     * ログアウト完了後に任意処理を差し込むためのフック。
     */
    protected function loggedOut(Request $request) {}

    /**
     * 認証に利用するガードインスタンスを取得する。
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * ログイン試行のスロットルが必要か判定する。
     */
    private function shouldThrottleLoginAttempts(Request $request): bool
    {
        if (! method_exists($this, 'hasTooManyLoginAttempts')) {
            return false;
        }

        if (! $this->hasTooManyLoginAttempts($request)) {
            return false;
        }

        $this->fireLockoutEvent($request);

        return true;
    }

    /**
     * パスワード確認済みタイムスタンプをセッションへ記録する。
     */
    private function storePasswordConfirmationTimestamp(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->put('auth.password_confirmed_at', time());
    }
}
