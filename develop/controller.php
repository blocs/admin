<?php

namespace App\Http\ControllersCONTROLLER_DIRNAME;

use Blocs\Controllers\Base;

class CONTROLLER_BASENAME extends Base
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
        $searchColumns = [SEARCH_LIST];

        foreach ($searchKeywords as $searchKeyword) {
            $mainTable->where(function ($query) use ($searchKeyword, $searchColumns) {
                foreach ($searchColumns as $searchColumn) {
                    $query->orWhere($searchColumn, 'LIKE', '%'.$searchKeyword.'%');
                }
            });
        }

        empty($searchColumns) || docs(array_map(
            fn ($searchColumn) => '<search>があれば、<'.$this->loopItem.'>の'.$searchColumn.'を<search>で部分一致検索',
            $searchColumns
        ));
    }

    private function prepareSortState(): void
    {
        if (empty($this->val['sort']) || ! is_array($this->val['sort'])) {
            $this->val['sort'] = [];
        } else {
            // 並び順は asc / desc だけを受け付ける（不正な値で一覧が開けなくなるのを防ぐ）
            $this->val['sort'] = array_filter(
                $this->val['sort'],
                fn ($direction) => is_string($direction) && in_array(strtolower($direction), ['asc', 'desc'], true)
            );
        }

        if (empty($this->val['sort'])) {
            $this->val['sort'] = DEFAULT_SORT;
        }
    }

    private function applySortOrders(&$mainTable): void
    {
        $sortableColumns = [SORT_LIST];

        foreach ($sortableColumns as $sortableColumn) {
            if (! empty($this->val['sort'][$sortableColumn])) {
                $mainTable->orderBy($sortableColumn, $this->val['sort'][$sortableColumn]);
            }
        }

        docs('指定された条件でソート');
    }
}
