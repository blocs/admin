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
        $preparedData = $this->prepareCopy();
        $this->executeCopy($preparedData);

        // コピー完了後の画面遷移
        docs('# 画面遷移');

        return $this->outputCopy();
    }

    protected function prepareCopy()
    {
        // テーブルデータを配列に変換
        $requestData = $this->tableData->toArray();

        // コピー対象外のフィールドを除外
        $this->removeCopyExcludedFields($requestData);

        return $requestData;
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

        // トランザクション内で新規データを作成
        $newRecord = $this->insertCopyRecordWithTransaction($requestData);

        if ($newRecord === null) {
            return;
        }

        // 作成したレコードのIDを設定
        $this->val['id'] = $newRecord->id;
        docs(null, '同じ内容の新しいデータを保存する', ['データベース' => $this->loopItem]);

        // ログ用のデータを準備
        $this->buildCopyLogData($requestData, $newRecord->id);
    }

    protected function outputCopy()
    {
        // コピー完了メッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_registered', $this->tableData[$this->noticeItem]);
    }

    private function removeCopyExcludedFields(&$requestData)
    {
        // コピー時に除外するフィールド（ID、タイムスタンプ系）を削除
        $excludedFields = $this->getExcludedFieldsForCopy();

        foreach ($excludedFields as $field) {
            unset($requestData[$field]);
        }
    }

    private function insertCopyRecordWithTransaction($requestData)
    {
        // データベーストランザクション内でコピーレコードを作成
        $newRecord = null;
        \Illuminate\Support\Facades\DB::transaction(function () use ($requestData, &$newRecord) {
            $newRecord = $this->mainTable::create($requestData);
        }, 10);

        return $newRecord;
    }

    private function buildCopyLogData($requestData, $newId)
    {
        // ログデータを準備（コピー内容 + 新規ID）
        $this->logData = (object) $requestData;
        $this->logData->id = $newId;
    }
}
