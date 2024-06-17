<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait DestroyTrait
{
    protected $deletedNum = 0;

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

        docs('# 画面表示');

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

        $view = view($this->viewPrefix.'.confirmDestroy', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('テンプレートを読み込んで、HTMLを生成');

        return $view;
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

        docs('# データの削除');
        $this->prepareDestroy();
        $this->executeDestroy();
        $this->logDestroy();

        docs('# 画面遷移');

        return $this->outputDestroy();
    }

    protected function prepareDestroy()
    {
    }

    protected function executeDestroy()
    {
        try {
            $this->deletedNum = $this->mainTable::destroy($this->val['id']);
        } catch (\Throwable $e) {
            throw $e;
        }
        docs(['GET' => 'id'], '<id>を指定してデータを削除', ['データベース' => $this->loopItem]);

        $this->logData = new \stdClass();
        $this->logData->id = $this->val['id'];
    }

    protected function outputDestroy()
    {
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }
}
