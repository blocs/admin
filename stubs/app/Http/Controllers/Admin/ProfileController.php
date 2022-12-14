<?php

namespace App\Http\Controllers\Admin;

class ProfileController extends UserController
{
    public function __construct()
    {
        define('ROUTE_PREFIX', 'profile');

        $this->viewPrefix = VIEW_PREFIX.'.'.ROUTE_PREFIX;
        $this->mainTable = 'App\Models\Admin\User';
        $this->noticeItem = 'email';
    }

    public function edit($id)
    {
        return parent::edit(\Auth::id());
    }

    protected function outputUpdate()
    {
        \Blocs\Notice::set('success', 'admin_profile_updated');

        return redirect()->route('home');
    }

    protected function prepareUpdate()
    {
        $requestData = parent::prepareUpdate();

        if (empty($this->request->file)) {
            // 画像ファイルの削除
            $requestData['file'] = null;
            $requestData['filename'] = null;
        } else {
            // 画像ファイルの登録
            $requestData['file'] = $this->request->file;

            $fileList = json_decode($requestData['file'], true);
            $requestData['filename'] = $fileList[0]['filename'];
        }

        return $requestData;
    }
}
