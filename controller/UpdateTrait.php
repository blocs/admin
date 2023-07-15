<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait UpdateTrait
{
    public function edit($id)
    {
        $this->getCurrent($id);
        $this->val['id'] = $id;

        empty(old()) && $this->val = array_merge($this->tableData->toArray(), $this->val);

        $this->prepareEdit();

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->val = array_merge($this->val, session($this->viewPrefix.'.confirm'));
        }

        return $this->outputEdit();
    }

    protected function prepareEdit()
    {
        return $this->prepareCreate();
    }

    protected function outputEdit()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.edit', $this->val);
    }

    /* show */

    public function show($id)
    {
        $this->getCurrent($id);
        $this->val['id'] = $id;

        $this->val = array_merge($this->tableData->toArray(), $this->val);

        $this->prepareShow();

        return $this->outputShow();
    }

    protected function prepareShow()
    {
    }

    protected function outputShow()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.show', $this->val);
    }

    /* update */

    public function confirmUpdate($id, Request $request)
    {
        $this->getCurrent($id);
        $this->val['id'] = $id;
        $this->request = $request;

        if ($redirect = $this->validateUpdate()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'.confirm', $this->request->all());

        $this->prepareConfirmUpdate();

        return $this->outputConfirmUpdate();
    }

    protected function validateUpdate()
    {
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.edit', $this->request);
        empty($rules) || $this->request->validate($rules, $messages);
    }

    protected function prepareConfirmUpdate()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmUpdate()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.confirmUpdate', $this->val);
    }

    public function update($id, Request $request)
    {
        $this->getCurrent($id);
        $this->val['id'] = $id;
        $this->request = $request;

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'.confirm'));
        } else {
            if ($redirect = $this->validateUpdate()) {
                return $redirect;
            }
        }

        if ($redirect = $this->checkConflict()) {
            return $redirect;
        }

        $this->executeUpdate($this->prepareUpdate());
        $this->logUpdate();

        return $this->outputUpdate();
    }

    protected function checkConflict()
    {
        if (empty($this->request->updated_at)) {
            return;
        }

        $tableData = $this->tableData->toArray();

        if ($this->request->updated_at !== $tableData['updated_at']) {
            return $this->backEdit('error', 'collision_happened');
        }
    }

    protected function prepareUpdate()
    {
        return $this->prepareStore();
    }

    protected function executeUpdate($requestData = [])
    {
        if (empty($requestData)) {
            return;
        }

        $this->tableData->fill($requestData)->save();

        $this->logData = (object) $requestData;
        $this->logData->id = $this->val['id'];
    }

    protected function outputUpdate()
    {
        if ($this->request->has($this->noticeItem)) {
            $noticeItem = $this->request->{$this->noticeItem};
        } else {
            $noticeItem = $this->tableData->{$this->noticeItem};
        }

        return $this->backIndex('success', 'data_updated', $noticeItem);
    }
}
