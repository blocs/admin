<?php

namespace App\Http\Controllers\Admin;

class ProfileController extends \Blocs\Controllers\Base
{
    use UserUpdateTrait;

    public function __construct()
    {
        $this->viewPrefix = 'admin.profile';
        $this->mainTable = 'App\Models\Admin\User';
        $this->noticeItem = 'email';
    }

    public function edit($id)
    {
        return parent::edit(\Auth::id());
    }

    protected function outputUpdate()
    {
        unset($this->val, $this->request, $this->tableData);

        return redirect()->route('home')->with([
            'category' => 'success',
            'message' => lang('success:admin_profile_updated'),
        ]);
    }

    protected function prepareUpdateTrait(&$requestData)
    {
        unset($requestData['role']);

        if ($this->request->has('file')) {
            if (empty($this->request->file)) {
                // 画像ファイルの削除
                $requestData['file'] = null;
            } else {
                // 画像ファイルの登録
                $requestData['file'] = $this->request->file;

                $fileList = json_decode($requestData['file'], true);
            }
        }
    }
}
