<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait Destroy
{
    private $tableData;
    private $deletedNum = 0;

    public function confirmDestroy($id, Request $request)
    {
        $this->getCurrent($id);
        $this->val['id'] = $id;
        $this->request = $request;

        if ($redirect = $this->validateDestroy()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'.confirm', $this->request->all());

        $this->prepareConfirmDestroy();

        return $this->outputConfirmDestroy();
    }

    protected function validateDestroy()
    {
    }

    protected function prepareConfirmDestroy()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmDestroy()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.confirmDestroy', $this->val);
    }

    public function destroy($id, Request $request)
    {
        $this->getCurrent($id);
        $this->val['id'] = $id;
        $this->request = $request;

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'.confirm'));
        } else {
            if ($redirect = $this->validateDestroy()) {
                return $redirect;
            }
        }

        $this->prepareDestroy();
        $this->executeDestroy();

        return $this->outputDestroy();
    }

    protected function prepareDestroy()
    {
    }

    protected function executeDestroy()
    {
        $this->deletedNum = call_user_func($this->mainTable.'::destroy', $this->val['id']);
    }

    protected function outputDestroy()
    {
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }
}
