<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait Select
{
    private $selectedIdList = [];
    private $deletedNum = 0;

    public function confirmSelect(Request $request)
    {
        $this->request = $request;

        if ($redirect = $this->validateSelect()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'.confirm', $this->request->all());

        $this->prepareConfirmSelect();

        return $this->outputConfirmSelect();
    }

    protected function validateSelect()
    {
        if (empty($this->request->{$this->loopItem})) {
            return $this->backIndex('error', 'data_not_selected');
        }

        foreach ($this->request->{$this->loopItem} as $table) {
            empty($table['selectedRows']) || $this->selectedIdList[] = $table['selectedRows'][0];
        }

        if (empty($this->selectedIdList)) {
            return $this->backIndex('error', 'data_not_selected');
        }
    }

    protected function prepareConfirmSelect()
    {
    }

    protected function outputConfirmSelect()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.confirmSelect', $this->val);
    }

    public function select(Request $request)
    {
        $this->request = $request;

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'.confirm'));

            foreach ($this->request->{$this->loopItem} as $table) {
                empty($table['selectedRows']) || $this->selectedIdList[] = $table['selectedRows'][0];
            }
        } else {
            if ($redirect = $this->validateSelect()) {
                return $redirect;
            }
        }

        $this->prepareSelect();
        $this->executeSelect();

        return $this->outputSelect();
    }

    protected function prepareSelect()
    {
    }

    protected function executeSelect()
    {
        if (empty($this->selectedIdList)) {
            return;
        }

        $this->deletedNum = call_user_func($this->mainTable.'::destroy', $this->selectedIdList);
    }

    protected function outputSelect()
    {
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }
}
