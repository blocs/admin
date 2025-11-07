<?php

namespace Blocs\Auth;

trait RedirectsUsers
{
    /**
     * ログイン後にリダイレクトするパスを決定する。
     *
     * @return string
     */
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
    }
}
