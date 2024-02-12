<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait StoreTrait
{
    public function create()
    {
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

        doc('画面表示');
        $view = view($this->viewPrefix.'.create', $this->val);
        unset($this->val, $this->request, $this->tableData);

        return $view;
    }

    /* store */

    public function confirmStore(Request $request)
    {
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
        doc(['POST' => '入力値', 'TEMPLATE' => $this->viewPrefix.'.create'], '入力値を新規作成画面の条件で検証');
        doc(null, 'エラーがあれば編集画面に戻る', ['FORWARD' => $this->viewPrefix.'.create']);
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.create', $this->request);
        empty($rules) || $this->request->validate($rules, $messages, $this->getLabel($this->viewPrefix.'.create'));
    }

    protected function prepareConfirmStore()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmStore()
    {
        $this->setupMenu();

        doc('画面表示');
        $view = view($this->viewPrefix.'.confirmStore', $this->val);
        unset($this->val, $this->request, $this->tableData);

        return $view;
    }

    public function store(Request $request)
    {
        $this->request = $request;

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'.confirm'));
        } else {
            doc('データの検証');
            if ($redirect = $this->validateStore()) {
                return $redirect;
            }
        }

        doc('データの追加');
        $this->executeStore($this->prepareStore());
        $this->logStore();

        return $this->outputStore();
    }

    protected function prepareStore()
    {
        $requestData = $this->request->all();

        foreach ($requestData as $key => $value) {
            if (is_array($value) && array_values($value) === $value) {
                if (count($value) && is_array($value[0])) {
                    continue;
                }

                // option項目
                $requestData[$key] = implode("\t", $value);
            }
        }

        return $requestData;
    }

    protected function executeStore($requestData = [])
    {
        if (empty($requestData)) {
            return;
        }

        doc(null, 'データを追加', ['データベース' => $this->loopItem]);
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
