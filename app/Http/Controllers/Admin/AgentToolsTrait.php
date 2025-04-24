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

        $this->val = array_merge($this->val, ['message' => $defaultMessage]);
    }

    private function checkLogin()
    {
        if (\Auth::id()) {
            return true;
        }

        session(['requests' => [$this->val['requests']]]);
        $this->setRequests('ログインする');

        return $this->authLogin();
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

    private function authLogin($email = null, $password = null)
    {
        if (\Auth::id()) {
            return [
                'message' => 'もうログインしています。',
            ];
        }

        if (!$email) {
            return $this->askText('LoginEmail');
        }

        if (!$password) {
            return $this->askText('LoginPassword');
        }

        $email = $this->getSecret($email);
        request()->merge(['email' => $email]);

        $password = $this->getSecret($password);
        request()->merge(['password' => $password]);

        return $this->login(request());
    }

    private function authLogout()
    {
        if (!\Auth::id()) {
            return [
                'message' => 'もうログアウトしています。',
            ];
        }

        return $this->logout(request());
    }

    private function userIndex($query = null)
    {
        if (true !== ($checkLogin = $this->checkLogin())) {
            return $checkLogin;
        }

        if (true !== ($checkRole = $this->checkRole(['admin']))) {
            return $checkRole;
        }

        if (!$query) {
            return redirect()->route('admin.user.index');
        }

        if ($id = $this->searchUser($query)) {
            return redirect()->route('admin.user.edit', ['id' => $id]);
        }

        return redirect()->route('admin.user.index', ['search' => $query]);
    }

    private function userCreate($email = null)
    {
        if (true !== ($checkLogin = $this->checkLogin())) {
            return $checkLogin;
        }

        if (true !== ($checkRole = $this->checkRole(['admin']))) {
            return $checkRole;
        }

        if (!$email) {
            return $this->askText('UserCreateEmail');
        }

        // 初期値をセット
        $email = $this->getSecret($email);
        session()->flashInput(['email' => $email]);

        return redirect()->route('admin.user.create');
    }

    private function userDestroy($query = null)
    {
        if (true !== ($checkLogin = $this->checkLogin())) {
            return $checkLogin;
        }

        if (true !== ($checkRole = $this->checkRole(['admin']))) {
            return $checkRole;
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
