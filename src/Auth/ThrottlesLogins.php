<?php

namespace Blocs\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait ThrottlesLogins
{
    /**
     * ログイン試行回数が上限を超えているか判定する。
     *
     * @return bool
     */
    protected function hasTooManyLoginAttempts(Request $request)
    {
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey($request), $this->maxAttempts()
        );
    }

    /**
     * ログイン試行回数をインクリメントする。
     *
     * @return void
     */
    protected function incrementLoginAttempts(Request $request)
    {
        $this->limiter()->hit(
            $this->throttleKey($request), $this->decayMinutes() * 60
        );
    }

    /**
     * ロックアウトされたユーザーをレスポンスで通知する。
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws ValidationException
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        throw ValidationException::withMessages([$this->username() => [trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])]])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * 指定された資格情報に対するロック状態をクリアする。
     *
     * @return void
     */
    protected function clearLoginAttempts(Request $request)
    {
        $this->limiter()->clear($this->throttleKey($request));
    }

    /**
     * ロックアウト発生時のイベントを発火する。
     *
     * @return void
     */
    protected function fireLockoutEvent(Request $request)
    {
        event(new Lockout($request));
    }

    /**
     * 現在のリクエスト用にスロットルキーを生成する。
     *
     * @return string
     */
    protected function throttleKey(Request $request)
    {
        return Str::transliterate(Str::lower($request->input($this->username())).'|'.$request->ip());
    }

    /**
     * レートリミッターのインスタンスを取得する。
     *
     * @return RateLimiter
     */
    protected function limiter()
    {
        return app(RateLimiter::class);
    }

    /**
     * 許可される最大試行回数を取得する。
     *
     * @return int
     */
    public function maxAttempts()
    {
        return property_exists($this, 'maxAttempts') ? $this->maxAttempts : 5;
    }

    /**
     * ロックアウトが解除されるまでの分数を取得する。
     *
     * @return int
     */
    public function decayMinutes()
    {
        return property_exists($this, 'decayMinutes') ? $this->decayMinutes : 1;
    }
}
