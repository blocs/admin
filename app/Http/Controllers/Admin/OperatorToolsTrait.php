<?php

namespace App\Http\Controllers\Admin;

trait OperatorToolsTrait
{
    use \Blocs\Auth\AuthenticatesUsers;

    private function tryLogin($request)
    {
        if (\Auth::id()) {
            return [
                'message' => 'もうログインしています。',
            ];
        }

        if (!strlen($request->email)) {
            return $this->askLoginEmail();
        }

        if (!strlen($request->password)) {
            return $this->askLoginPassword();
        }

        if ($sessionValue = session($request->email)) {
            $request->merge([
                'email' => $sessionValue,
            ]);
        }

        if ($sessionValue = session($request->password)) {
            $request->merge([
                'password' => $sessionValue,
            ]);
        }

        return $this->login($request);
    }

    private function askLoginEmail()
    {
        $this->val = array_merge($this->val, [
            'message' => 'ログインするので、メールアドレスを入力してください。',
            'template' => 'ログインメールアドレスは{request}です。',
            'secret' => true,
        ]);

        $view = view($this->viewPrefix.'.operator', $this->val);

        return $view;
    }

    private function askLoginPassword()
    {
        $this->val = array_merge($this->val, [
            'message' => 'ログインするので、パスワードを入力してください。',
            'template' => 'ログインパスワードは{request}です。',
            'secret' => true,
            'type' => 'password',
        ]);

        $view = view($this->viewPrefix.'.operator', $this->val);

        return $view;
    }

    private function moveToHome()
    {
        if (!\Auth::id()) {
            session(['requests' => [$this->val['requests']]]);

            return $this->tryLogin(request());
        }

        return redirect()->route('home');
    }

    private function moveToProfile()
    {
        if (!\Auth::id()) {
            session(['requests' => [$this->val['requests']]]);

            return $this->tryLogin(request());
        }

        return redirect()->route('profile.edit', ['id' => 0]);
    }

    private function moveToUser($request)
    {
        if (!\Auth::id()) {
            session(['requests' => [$this->val['requests']]]);

            return $this->tryLogin(request());
        }

        if (!$this->checkRole('admin')) {
            return [
                'message' => '権限がありません。',
            ];
        }

        if (!$request->search) {
            return redirect()->route('admin.user.index');
        }

        if ($id = $this->searchUser($request->search)) {
            return redirect()->route('admin.user.edit', ['id' => $id]);
        }

        return redirect()->route('admin.user.index', ['search' => $request->search]);
    }

    private function checkRole($role)
    {
        $myRoleList = explode("\t", \Auth::user()->role);

        return in_array($role, $myRoleList);
    }

    private function searchUser($search)
    {
        $result = \App\Models\Admin\User::where('name', 'LIKE', '%'.$search.'%')
            ->orWhere('email', 'LIKE', '%'.$search.'%')
            ->orWhere('role', 'LIKE', '%'.$search.'%');

        if (1 !== $result->count()) {
            return false;
        }

        return $result->first()->id;
    }

    private function moveToUserCreate()
    {
        if (!\Auth::id()) {
            session(['requests' => [$this->val['requests']]]);

            return $this->tryLogin(request());
        }

        if (!$this->checkRole('admin')) {
            return [
                'message' => '権限がありません。',
            ];
        }

        return redirect()->route('admin.user.create');
    }

    private function moveToUserDestroy($request)
    {
        if (!\Auth::id()) {
            session(['requests' => [$this->val['requests']]]);

            return $this->tryLogin(request());
        }

        if (!$this->checkRole('admin')) {
            return [
                'message' => '権限がありません。',
            ];
        }

        if (!$request->search) {
            return redirect()->route('admin.user.index');
        }

        if ($id = $this->searchUser($request->search)) {
            return redirect()->route('admin.user.show', ['id' => $id]);
        }

        return redirect()->route('admin.user.index', ['search' => $request->search]);
    }
}
