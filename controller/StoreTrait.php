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

        doc('# 画面表示');

        return $this->outputCreate();
    }

    protected function prepareCreate()
    {
    }

    protected function outputCreate()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.create', $this->val);
        unset($this->val, $this->request, $this->tableData);
        doc('テンプレートを読み込んで、HTMLを生成');

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

        doc('# 画面表示');

        return $this->outputConfirmStore();
    }

    protected function validateStore()
    {
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.create', $this->request);
        if (!empty($rules)) {
            $labels = $this->getLabel($this->viewPrefix.'.create');
            $this->request->validate($rules, $messages, $labels);
            $validates = $this->getValidate($rules, $messages, $labels);
            doc(['POST' => '入力値'], '入力値を以下の条件で検証して、エラーがあればメッセージをセット', null, $validates);
            doc(null, 'エラーがあれば、編集画面に戻る', ['FORWARD' => $this->viewPrefix.'.create']);
        }
    }

    protected function prepareConfirmStore()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmStore()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.confirmStore', $this->val);
        unset($this->val, $this->request, $this->tableData);
        doc('テンプレートを読み込んで、HTMLを生成');

        return $view;
    }

    public function store(Request $request)
    {
        $this->request = $request;

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->request->merge(session($this->viewPrefix.'.confirm'));
        } else {
            doc('# データの検証');
            if ($redirect = $this->validateStore()) {
                return $redirect;
            }
        }

        doc('# データの追加');
        $this->executeStore($this->prepareStore());
        $this->logStore();

        doc('# 画面遷移');

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

        $lastInsert = $this->mainTable::create($requestData);
        $this->val['id'] = $lastInsert->id;
        doc(null, 'データを追加', ['データベース' => $this->loopItem]);

        $this->logData = (object) $requestData;
        $this->logData->id = $lastInsert->id;
    }

    protected function outputStore()
    {
        return $this->backIndex('success', 'data_registered', $this->request->{$this->noticeItem});
    }
}
