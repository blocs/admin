<?php

namespace Blocs\Controllers;

trait CopyTrait
{
    protected $copyId;

    public function copy($id)
    {
        // コピー元のデータを取得してコピーIDを設定
        $this->getCurrent($id);
        $this->copyId = $id;

        // データのコピー処理を実行
        docs(['GET' => 'id'], '# データのコピー');
        $this->executeCopy($this->prepareCopy());

        // コピー完了後の画面遷移
        docs('# 画面遷移');

        return $this->outputCopy();
    }

    protected function prepareCopy()
    {
        // テーブルデータを配列に変換し、コピー対象外のフィールドを除外
        return array_diff_key($this->tableData->toArray(), array_flip($this->getExcludedFieldsForCopy()));
    }

    protected function getExcludedFieldsForCopy()
    {
        // コピー時に新規採番・再設定されるべきフィールドのリスト
        return ['id', 'created_at', 'updated_at', 'deleted_at', 'disabled_at'];
    }

    protected function executeCopy($requestData = [])
    {
        // 空データの場合は処理をスキップ
        if (empty($requestData)) {
            return;
        }

        $newRecord = $this->createRecord($requestData);
        if ($newRecord === null) {
            return;
        }

        // 作成したレコードのIDを設定
        $this->val['id'] = $newRecord->id;
        docs(null, '同じ内容の新しいデータを保存する', ['データベース' => $this->loopItem]);

        $this->setLogData($requestData, $newRecord->id);
    }

    protected function outputCopy()
    {
        // コピー完了メッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_registered', $this->tableData[$this->noticeItem]);
    }
}
