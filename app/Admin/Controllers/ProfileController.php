<?php

namespace App\Admin\Controllers;

class ProfileController extends \Blocs\Controllers\Base
{
    use UserUpdateTrait;

    public function __construct()
    {
        defined('BLOCS_AUTOINCLUDE_DIR') || define('BLOCS_AUTOINCLUDE_DIR', 'admin');
        parent::__construct();

        $this->viewPrefix = ADMIN_VIEW_PREFIX.'.profile';
        $this->mainTable = 'App\Models\Admin\User';
        $this->noticeItem = 'email';
    }

    public function edit($id)
    {
        return parent::edit(\Auth::id());
    }

    protected function outputUpdate()
    {
        return redirect()->route('home')->with([
            'category' => 'success',
            'message' => \Blocs\Lang::get('success:admin_profile_updated'),
        ]);
    }

    protected function prepareUpdateTrait(&$requestData)
    {
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

        unset($requestData['role']);
    }
}
