<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait StoreTrait
{
    public function create()
    {
        $this->val = [];

        $this->prepareCreate();

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->val = array_merge($this->val, session($this->viewPrefix.'.confirm'));
        }

        return $this->outputCreate();
    }

    protected function prepareCreate()
    {
    }

    protected function outputCreate()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.create', $this->val);
    }

    /* store */

    public function confirmStore(Request $request)
    {
        $this->val = [];
        $this->request = $request;

        if ($redirect = $this->validateStore()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'.confirm', $this->request->all());

        $this->prepareConfirmStore();

        return $this->outputConfirmStore();
    }

    protected function validateStore()
    {
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.create', $this->request);
        empty($rules) || $this->request->validate($rules, $messages);
    }

    protected function prepareConfirmStore()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmStore()
    {
        $this->setupMenu();

        return view($this->viewPrefix.'.confirmStore', $this->val);
    }

    public function store(Request $request)
    {
        $this->val = [];
        $this->request = $request;

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'.confirm'));
        } else {
            if ($redirect = $this->validateStore()) {
                return $redirect;
            }
        }

        $this->executeStore($this->prepareStore());
        $this->logStore();

        return $this->outputStore();
    }

    protected function prepareStore()
    {
    }

    protected function executeStore($requestData = [])
    {
        if (empty($requestData)) {
            return;
        }

        $lastInsert = $this->mainTable::create($requestData);
        $this->val['id'] = $lastInsert->id;

        $this->logData = (object) $requestData;
        $this->logData->id = $lastInsert->id;
    }

    protected function outputStore()
    {
        return $this->backIndex('success', 'data_registered', $this->request->{$this->noticeItem});
    }
}
