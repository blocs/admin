<?php

namespace Blocs\Controllers;

trait ToggleTrait
{
    public function toggle($id)
    {
        doc(['POST' => 'id'], '# 現データの取得');
        $this->getCurrent($id);
        $this->val['id'] = $id;

        // 有効と無効の切替
        $this->tableData->disabled_at = empty($this->tableData->disabled_at);
        $this->tableData->save();
        doc(null, "データの更新\nデータ有効ならば無効に、無効ならば有効に変更", ['データベース' => $this->loopItem]);

        doc('# 画面遷移');

        return $this->outputToggle();
    }

    protected function outputToggle()
    {
        if (empty($this->tableData->disabled_at)) {
            return $this->backIndex('success', 'data_valid', $this->tableData->{$this->noticeItem});
        }

        return $this->backIndex('success', 'data_invalid', $this->tableData->{$this->noticeItem});
    }
}
