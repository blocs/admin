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
        // 検索
        foreach ($this->searchItems as $searchItem) {
            $mainTable->where(function ($query) use ($searchItem) {
                $query
                    ->where('item1', 'LIKE', '%'.$searchItem.'%')
                    ->orWhere('item2', 'LIKE', '%'.$searchItem.'%');
            });
        }
        docs([
            '<search>があれば、<'.$this->loopItem.'>のitem1を<search>で部分一致検索',
            '<search>があれば、<'.$this->loopItem.'>のitem2を<search>で部分一致検索',
        ]);

        // ソート
        $this->keepItem('sort');

        // ソート条件の初期化とバリデーション
        if (empty($this->val['sort']) || ! is_array($this->val['sort'])) {
            $this->val['sort'] = [];
        } else {
            $this->val['sort'] = array_filter($this->val['sort'], 'strlen');
        }

        // ソート条件が空の場合、デフォルト値を設定
        if (empty($this->val['sort'])) {
            $this->val['sort'] = ['item1' => 'asc'];
        }

        // 指定された条件でソート
        $allowedSortItems = ['item1', 'item2', 'item3'];
        foreach ($allowedSortItems as $sortItem) {
            if (! empty($this->val['sort'][$sortItem])) {
                $mainTable->orderBy($sortItem, $this->val['sort'][$sortItem]);
            }
        }
        docs('指定された条件でソート');
    }
}
