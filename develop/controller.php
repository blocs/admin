<?php

namespace App\Http\ControllersCONTROLLER_DIRNAME;

class CONTROLLER_BASENAME extends \Blocs\Controllers\Base
{
    public function __construct()
    {
        $this->viewPrefix = 'VIEW_PREFIX';
        $this->mainTable = 'App\ModelsMODEL_DIRNAME\MODEL_BASENAME';
        $this->loopItem = 'LOOP_ITEM';
        $this->paginateNum = PAGINATE_NUM;
        $this->noticeItem = 'NOTICE_ITEM';
    }

    protected function prepareIndexSearch(&$mainTable)
    {
        // 検索条件をもとに部分一致フィルタを設定
        $this->applySearchFilters($mainTable);

        // ソート条件として使用する入力値を保持する
        $this->keepItem('sort');

        // ソート条件を正規化して初期値を整備
        $this->prepareSortState();

        // 受け付けたソート条件を実行
        $this->applySortOrders($mainTable);
    }

    private function applySearchFilters(&$mainTable): void
    {
        $searchKeywords = $this->searchItems ?? [];

        foreach ($searchKeywords as $searchKeyword) {
            $mainTable->where(function ($query) use ($searchKeyword) {
                $query
                    ->where('item1', 'LIKE', '%'.$searchKeyword.'%')
                    ->orWhere('item2', 'LIKE', '%'.$searchKeyword.'%');
            });
        }

        docs([
            '<search>があれば、<'.$this->loopItem.'>のitem1を<search>で部分一致検索',
            '<search>があれば、<'.$this->loopItem.'>のitem2を<search>で部分一致検索',
        ]);
    }

    private function prepareSortState(): void
    {
        if (empty($this->val['sort']) || ! is_array($this->val['sort'])) {
            $this->val['sort'] = [];
        } else {
            $this->val['sort'] = array_filter($this->val['sort'], 'strlen');
        }

        if (empty($this->val['sort'])) {
            $this->val['sort'] = ['item1' => 'asc'];
        }
    }

    private function applySortOrders(&$mainTable): void
    {
        $sortableColumns = ['item1', 'item2', 'item3'];

        foreach ($sortableColumns as $sortableColumn) {
            if (! empty($this->val['sort'][$sortableColumn])) {
                $mainTable->orderBy($sortableColumn, $this->val['sort'][$sortableColumn]);
            }
        }

        docs('指定された条件でソート');
    }
}
