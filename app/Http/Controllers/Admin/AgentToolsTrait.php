<?php

namespace App\Http\Controllers\Admin;

trait AgentToolsTrait
{
    use \Blocs\Auth\AuthenticatesUsers;

    private function initMessage()
    {
        if (\Auth::user()) {
            $defaultMessage = \Auth::user()->name.'さん、何かリクエストを入力してください。';
        } else {
            $defaultMessage = '何かリクエストを入力してください。';
        }

        $this->val = array_merge($this->val, [
            'message' => $defaultMessage,
        ]);
    }

    private function checkLogin()
    {
        if (\Auth::id()) {
            return true;
        }

        session(['requests' => [$this->val['requests']]]);
        $this->setRequests('ログインする');

        return $this->tryLogin();
    }

    private function checkRole($roles)
    {
        $myRoleList = explode("\t", \Auth::user()->role);

        foreach ($roles as $role) {
            if (in_array($role, $myRoleList)) {
                return true;
            }
        }

        return [
            'message' => '権限がありません。',
        ];
    }

    private function tryLogin($email = null, $password = null)
    {
        if (\Auth::id()) {
            return [
                'message' => 'もうログインしています。',
            ];
        }

        if (!strlen($email)) {
            return $this->askText('LoginEmail');
        }

        ($sessionValue = session($email)) && $email = $sessionValue;
        request()->merge([
            'email' => $email,
        ]);

        if (!strlen($password)) {
            return $this->askText('LoginPassword');
        }

        ($sessionValue = session($password)) && $password = $sessionValue;
        request()->merge([
            'password' => $password,
        ]);

        return $this->login(request());
    }

    private function tryLogout()
    {
        if (!\Auth::id()) {
            return [
                'message' => 'もうログアウトしています。',
            ];
        }

        return $this->logout(request());
    }

    private function redirectUser($query = null)
    {
        if (true !== ($responseLogin = $this->checkLogin())) {
            return $responseLogin;
        }

        if (true !== ($responseRole = $this->checkRole(['admin']))) {
            return $responseRole;
        }

        if (!$query) {
            return redirect()->route('admin.user.index');
        }

        if ($id = $this->searchUser($query)) {
            return redirect()->route('admin.user.edit', ['id' => $id]);
        }

        return redirect()->route('admin.user.index', ['search' => $query]);
    }

    private function redirectUserCreate($email = null)
    {
        if (true !== ($responseLogin = $this->checkLogin())) {
            return $responseLogin;
        }

        if (true !== ($responseRole = $this->checkRole(['admin']))) {
            return $responseRole;
        }

        if (!strlen($email)) {
            return $this->askText('UserCreateEmail');
        }

        // 初期値をセット
        ($sessionValue = session($email)) && $email = $sessionValue;
        session()->flash('_old_input', ['email' => $email]);

        return redirect()->route('admin.user.create');
    }

    private function redirectUserDestroy($query = null)
    {
        if (true !== ($responseLogin = $this->checkLogin())) {
            return $responseLogin;
        }

        if (true !== ($responseRole = $this->checkRole(['admin']))) {
            return $responseRole;
        }

        if (!$query) {
            return redirect()->route('admin.user.index');
        }

        if ($id = $this->searchUser($query)) {
            return redirect()->route('admin.user.show', ['id' => $id]);
        }

        return redirect()->route('admin.user.index', ['search' => $query]);
    }

    private function searchUser($query)
    {
        $result = \App\Models\Admin\User::where('name', 'LIKE', '%'.$query.'%')
            ->orWhere('email', 'LIKE', '%'.$query.'%')
            ->orWhere('role', 'LIKE', '%'.$query.'%');

        if (1 !== $result->count()) {
            return false;
        }

        return $result->first()->id;
    }
}
