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

        return $this->outputConfirmSelect();
    }

    protected function validateSelect()
    {
        doc(['POST' => '選択データ'], "データが選択されていなければ、メッセージをセットして一覧画面に戻る\n".lang('error:data_not_selected'), ['FORWARD' => $this->viewPrefix.'.index']);
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

        doc('画面表示');
        $view = view($this->viewPrefix.'.confirmSelect', $this->val);
        unset($this->val, $this->request, $this->tableData);

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
            doc('データの検証');
            if ($redirect = $this->validateSelect()) {
                return $redirect;
            }
        }

        doc('データの一括処理');
        $this->prepareSelect();
        $this->executeSelect();
        $this->logSelect();

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

        doc(['POST' => '選択データ'], 'データを削除', ['データベース' => $this->loopItem]);
        $this->deletedNum = $this->mainTable::destroy($this->selectedIdList);

        $this->logData = new \stdClass();
        $this->logData->id = $this->selectedIdList;
    }

    protected function outputSelect()
    {
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }
}
