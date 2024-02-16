<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait UpdateTrait
{
    public function edit($id)
    {
        doc(['POST' => 'id'], '# 現データの取得');
        $this->getCurrent($id);
        $this->val['id'] = $id;

        if (empty(old())) {
            $this->val = array_merge($this->getAccessor($this->tableData), $this->val);
            $this->val = array_merge($this->tableData->toArray(), $this->val);
        }

        $this->prepareEdit();

        if (session()->has($this->viewPrefix.'.confirm')) {
            // 確認画面からの遷移
            $this->val = array_merge($this->val, session($this->viewPrefix.'.confirm'));
        }

        doc('# 画面表示');

        return $this->outputEdit();
    }

    protected function prepareEdit()
    {
        return $this->prepareCreate();
    }

    protected function outputEdit()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.edit', $this->val);
        unset($this->val, $this->request, $this->tableData);
        doc('テンプレートを読み込んで、HTMLを生成');

        return $view;
    }

    /* show */

    public function show($id)
    {
        doc(['POST' => 'id'], '# 現データの取得');
        $this->getCurrent($id);
        $this->val['id'] = $id;

        $this->val = array_merge($this->getAccessor($this->tableData), $this->val);
        $this->val = array_merge($this->tableData->toArray(), $this->val);

        $this->prepareShow();

        doc('# 画面表示');

        return $this->outputShow();
    }

    protected function prepareShow()
    {
    }

    protected function outputShow()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.show', $this->val);
        unset($this->val, $this->request, $this->tableData);
        doc('テンプレートを読み込んで、HTMLを生成');

        return $view;
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

        doc('# 画面表示');

        return $this->outputConfirmUpdate();
    }

    protected function validateUpdate()
    {
        list($rules, $messages) = \Blocs\Validate::get($this->viewPrefix.'.edit', $this->request);
        if (!empty($rules)) {
            $labels = $this->getLabel($this->viewPrefix.'.edit');
            $this->request->validate($rules, $messages, $labels);
            $validates = $this->getValidate($rules, $messages, $labels);
            doc(['POST' => '入力値'], '入力値を以下の条件で検証して、エラーがあればメッセージをセット', null, $validates);
            doc(null, 'エラーがあれば、編集画面に戻る', ['FORWARD' => $this->viewPrefix.'.edit']);
        }
    }

    protected function prepareConfirmUpdate()
    {
        $this->val = array_merge($this->request->all(), $this->val);
    }

    protected function outputConfirmUpdate()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.confirmUpdate', $this->val);
        unset($this->val, $this->request, $this->tableData);
        doc('テンプレートを読み込んで、HTMLを生成');

        return $view;
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
            doc('# データの検証');
            if ($redirect = $this->validateUpdate()) {
                return $redirect;
            }
        }

        if ($redirect = $this->checkConflict()) {
            return $redirect;
        }

        doc('# データの更新');
        $this->executeUpdate($this->prepareUpdate());
        $this->logUpdate();

        doc('# 画面遷移');

        return $this->outputUpdate();
    }

    protected function checkConflict()
    {
        if (empty($this->request->updated_at)) {
            return;
        }

        $tableData = $this->tableData->toArray();

        doc('データの衝突チェック');
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
        doc(null, 'データを更新', ['データベース' => $this->loopItem]);

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
