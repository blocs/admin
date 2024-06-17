<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait SelectTrait
{
    protected $selectedIdList = [];
    protected $deletedNum = 0;

    public function confirmSelect(Request $request)
    {
        $this->request = $request;

        if ($redirect = $this->validateSelect()) {
            return $redirect;
        }

        session()->flash($this->viewPrefix.'.confirm', $this->request->all());

        $this->prepareConfirmSelect();

        docs('# 画面表示');

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
        docs(['POST' => '選択したデータのid'], "データが選択されていなければ、メッセージをセットして一覧画面に戻る\n・".lang('error:data_not_selected'), ['FORWARD' => $this->viewPrefix.'.index']);
    }

    protected function prepareConfirmSelect()
    {
    }

    protected function outputConfirmSelect()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.confirmSelect', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('テンプレートを読み込んで、HTMLを生成');

        return $view;
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
            docs('# データの検証');
            if ($redirect = $this->validateSelect()) {
                return $redirect;
            }
        }

        docs('# データの一括処理');
        $this->prepareSelect();
        $this->executeSelect();
        $this->logSelect();

        docs('# 画面遷移');

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

        try {
            $this->deletedNum = $this->mainTable::destroy($this->selectedIdList);
        } catch (\Throwable $e) {
            throw $e;
        }
        docs(['POST' => '選択したデータのid'], '<id>を指定してデータを一括削除', ['データベース' => $this->loopItem]);

        $this->logData = new \stdClass();
        $this->logData->id = $this->selectedIdList;
    }

    protected function outputSelect()
    {
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }
}
