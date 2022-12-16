<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

class ProfileController extends UserController
{
    public function __construct()
    {
        define('VIEW_PREFIX', 'admin');
        define('ROUTE_PREFIX', 'profile');

        $this->viewPrefix = VIEW_PREFIX.'.'.ROUTE_PREFIX;
        $this->mainTable = 'App\Models\Admin\User';
        $this->noticeItem = 'email';
    }

    public function entry($id = 0)
    {
        return parent::entry(\Auth::id());
    }

    public function update($id, Request $request)
    {
        return parent::update(\Auth::id(), $request);
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
