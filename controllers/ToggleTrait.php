<?php

namespace Blocs\Controllers;

trait ToggleTrait
{
    public function toggle($id)
    {
        docs('# データの更新');

        // 対象データを取得してIDを設定
        $this->initializeToggleContext($id);

        // データの有効/無効を切り替える処理を実行
        $this->executeToggleStatusChange();

        docs(['GET' => 'id', 'データベース' => $this->loopItem], "idを指定してデータを更新\nデータ有効ならば無効に、無効ならば有効に変更", ['データベース' => $this->loopItem]);

        docs('# 画面遷移');

        return $this->outputToggle();
    }

    protected function outputToggle()
    {
        // データが有効な場合は有効化のメッセージと共に一覧画面へ戻る
        if (empty($this->tableData->disabled_at)) {
            return $this->backIndex('success', 'data_valid', $this->tableData->{$this->noticeItem});
        }

        // データが無効な場合は無効化のメッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_invalid', $this->tableData->{$this->noticeItem});
    }

    private function initializeToggleContext($id)
    {
        // 切り替え対象のデータをデータベースから取得
        $this->getCurrent($id);

        // IDを設定
        $this->val['id'] = $id;
    }

    private function executeToggleStatusChange()
    {
        // トランザクション内でデータの有効/無効を切り替える
        $tableData = $this->tableData;
        \Illuminate\Support\Facades\DB::transaction(function () use ($tableData) {
            $tableData->disabled_at = empty($tableData->disabled_at);
            $tableData->save();
        }, 10);
    }
}
